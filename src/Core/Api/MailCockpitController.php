<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Core\Api;

use Hug\MailCockpit\Core\Content\MailReference\MailReferenceDefinition;
use Hug\MailCockpit\Exception\MailCockpitException;
use Hug\MailCockpit\Service\HugMailSender;
use Hug\MailCockpit\Service\MailArchiveGateway;
use Hug\MailCockpit\Service\MailContext;
use Hug\MailCockpit\Service\MailContextBuilder;
use Hug\MailCockpit\Service\SendMailCommand;
use Hug\MailCockpit\Service\TemplatePreviewRenderer;
use Hug\MailCockpit\Service\TwigContentPolicy;
use Shopware\Core\Framework\Api\ApiException;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Free mail dispatch is an abuse vector (konzept.md §7): every route enforces
 * its privileges server-side — either via the declarative `_acl` route default
 * (validated by the core AclAnnotationValidator) or imperatively where the
 * required privilege depends on the payload (send/preview/variables).
 */
#[Route(defaults: ['_routeScope' => ['api']])]
class MailCockpitController
{
    private const PRIVILEGE_VIEWER = 'hug_mail_cockpit.viewer';
    private const PRIVILEGE_SENDER = 'hug_mail_cockpit.sender';
    private const PRIVILEGE_FREE_SENDER = 'hug_mail_cockpit.free_sender';
    private const PRIVILEGE_TWIG_EDITOR = 'hug_mail_cockpit.twig_editor';

    public function __construct(
        private readonly HugMailSender $mailSender,
        private readonly MailContextBuilder $contextBuilder,
        private readonly TemplatePreviewRenderer $previewRenderer,
        private readonly TwigContentPolicy $twigContentPolicy,
        private readonly MailArchiveGateway $mailArchiveGateway,
    ) {
    }

    #[Route(
        path: '/api/_action/hug-mail-cockpit/send',
        name: 'api.action.hug-mail-cockpit.send',
        defaults: ['auth_required' => true],
        methods: ['POST'],
    )]
    public function send(Request $request, Context $context): JsonResponse
    {
        $payload = $this->decodePayload($request);
        $source = $this->stringValue($payload, 'source') ?? MailReferenceDefinition::SOURCE_FREE;

        // Document mails (F3) need `sender`, free mails (F1) the stricter `free_sender`.
        $requiredPrivilege = $source === MailReferenceDefinition::SOURCE_DOCUMENT
            ? self::PRIVILEGE_SENDER
            : self::PRIVILEGE_FREE_SENDER;

        if (!$context->isAllowed($requiredPrivilege)) {
            throw ApiException::missingPrivileges([$requiredPrivilege]);
        }

        $subject = $this->requireStringValue($payload, 'subject');
        $contentHtml = $this->requireStringValue($payload, 'contentHtml');

        $this->enforceTwigPolicy([$subject, $contentHtml], $context);

        $command = new SendMailCommand(
            orderId: $this->stringValue($payload, 'orderId'),
            customerId: $this->stringValue($payload, 'customerId'),
            recipients: $this->recipientMap($payload, 'recipients'),
            subject: $subject,
            contentHtml: $contentHtml,
            cc: $this->recipientMap($payload, 'cc', true),
            bcc: $this->recipientMap($payload, 'bcc', true),
            mailTemplateId: $this->stringValue($payload, 'mailTemplateId'),
            documentIds: $this->stringList($payload, 'documentIds'),
            mediaIds: $this->stringList($payload, 'mediaIds'),
            source: $source,
        );

        $this->mailSender->send($command, $context);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(
        path: '/api/_action/hug-mail-cockpit/preview',
        name: 'api.action.hug-mail-cockpit.preview',
        defaults: ['auth_required' => true],
        methods: ['POST'],
    )]
    public function preview(Request $request, Context $context): JsonResponse
    {
        $this->ensureAnyPrivilege($context, [self::PRIVILEGE_FREE_SENDER, self::PRIVILEGE_SENDER]);

        $payload = $this->decodePayload($request);
        $subject = $this->requireStringValue($payload, 'subject');
        $contentHtml = $this->requireStringValue($payload, 'contentHtml');

        // Without this check the preview would be a Twig oracle for users
        // that are not allowed to write Twig in the first place.
        $this->enforceTwigPolicy([$subject, $contentHtml], $context);

        $mailContext = $this->buildMailContext(
            $this->stringValue($payload, 'orderId'),
            $this->stringValue($payload, 'customerId'),
            $context,
        );

        $result = $this->previewRenderer->render($subject, $contentHtml, $mailContext->templateData, $mailContext->context);

        return new JsonResponse([
            'subject' => $result->subject,
            'contentHtml' => $result->contentHtml,
            'errors' => $result->errors,
        ]);
    }

    #[Route(
        path: '/api/_action/hug-mail-cockpit/variables',
        name: 'api.action.hug-mail-cockpit.variables',
        defaults: ['auth_required' => true],
        methods: ['GET'],
    )]
    public function variables(Request $request, Context $context): JsonResponse
    {
        $this->ensureAnyPrivilege($context, [self::PRIVILEGE_FREE_SENDER, self::PRIVILEGE_SENDER]);

        $mailContext = $this->buildMailContext(
            $this->queryString($request, 'orderId'),
            $this->queryString($request, 'customerId'),
            $context,
        );

        return new JsonResponse([
            'variables' => $this->contextBuilder->getVariables($mailContext),
            'languageId' => $mailContext->languageId,
            'salesChannelId' => $mailContext->salesChannelId,
            'recipientEmail' => $mailContext->recipientEmail,
            'recipientName' => $mailContext->recipientName,
        ]);
    }

    #[Route(
        path: '/api/_action/hug-mail-cockpit/history',
        name: 'api.action.hug-mail-cockpit.history',
        defaults: ['auth_required' => true, '_acl' => [self::PRIVILEGE_VIEWER]],
        methods: ['GET'],
    )]
    public function history(Request $request, Context $context): JsonResponse
    {
        $entries = $this->mailArchiveGateway->getHistory(
            $this->queryString($request, 'orderId'),
            $this->queryString($request, 'customerId'),
            $context,
        );

        return new JsonResponse([
            'entries' => $entries,
            'total' => \count($entries),
        ]);
    }

    private function buildMailContext(?string $orderId, ?string $customerId, Context $context): MailContext
    {
        if ($orderId !== null) {
            return $this->contextBuilder->buildOrderContext($orderId, $context);
        }

        if ($customerId !== null) {
            return $this->contextBuilder->buildCustomerContext($customerId, $context);
        }

        throw MailCockpitException::missingTarget();
    }

    /**
     * @param list<string> $privileges
     */
    private function ensureAnyPrivilege(Context $context, array $privileges): void
    {
        foreach ($privileges as $privilege) {
            if ($context->isAllowed($privilege)) {
                return;
            }
        }

        throw ApiException::missingPrivileges($privileges);
    }

    /**
     * @param list<string> $contents
     */
    private function enforceTwigPolicy(array $contents, Context $context): void
    {
        if ($context->isAllowed(self::PRIVILEGE_TWIG_EDITOR)) {
            return;
        }

        foreach ($contents as $content) {
            if ($this->twigContentPolicy->requiresTwigEditor($content)) {
                throw MailCockpitException::twigEditorPrivilegeRequired();
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(Request $request): array
    {
        $payload = json_decode($request->getContent(), true);

        if (!\is_array($payload)) {
            throw MailCockpitException::invalidPayload('body');
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function stringValue(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        if ($value === null || $value === '') {
            return null;
        }

        if (!\is_string($value)) {
            throw MailCockpitException::invalidPayload($key);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requireStringValue(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        if (!\is_string($value)) {
            throw MailCockpitException::invalidPayload($key);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, string> e-mail address => display name
     */
    private function recipientMap(array $payload, string $key, bool $optional = false): array
    {
        $value = $payload[$key] ?? ($optional ? [] : null);

        if (!\is_array($value)) {
            throw MailCockpitException::invalidPayload($key);
        }

        $map = [];
        foreach ($value as $email => $name) {
            if (!\is_string($email) || !\is_string($name)) {
                throw MailCockpitException::invalidPayload($key);
            }

            $map[$email] = $name;
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<string>
     */
    private function stringList(array $payload, string $key): array
    {
        $value = $payload[$key] ?? [];

        if (!\is_array($value)) {
            throw MailCockpitException::invalidPayload($key);
        }

        $list = [];
        foreach ($value as $item) {
            if (!\is_string($item)) {
                throw MailCockpitException::invalidPayload($key);
            }

            $list[] = $item;
        }

        return $list;
    }

    private function queryString(Request $request, string $key): ?string
    {
        $value = $request->query->get($key);

        return \is_string($value) && $value !== '' ? $value : null;
    }
}
