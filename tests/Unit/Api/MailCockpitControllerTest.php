<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Api;

use Hug\MailCockpit\Core\Api\MailCockpitController;
use Hug\MailCockpit\Core\Content\MailReference\MailReferenceDefinition;
use Hug\MailCockpit\Exception\MailCockpitException;
use Hug\MailCockpit\Service\HugMailSender;
use Hug\MailCockpit\Service\MailArchiveGateway;
use Hug\MailCockpit\Service\MailContext;
use Hug\MailCockpit\Service\MailContextBuilder;
use Hug\MailCockpit\Service\MailLetterheadLoader;
use Hug\MailCockpit\Service\MailTemplateGateway;
use Hug\MailCockpit\Service\PreviewResult;
use Hug\MailCockpit\Service\SendMailCommand;
use Hug\MailCockpit\Service\TemplatePreviewRenderer;
use Hug\MailCockpit\Service\TwigContentPolicy;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Exception\MissingPrivilegeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

class MailCockpitControllerTest extends TestCase
{
    private HugMailSender&MockObject $sender;

    private MailContextBuilder&MockObject $contextBuilder;

    private TemplatePreviewRenderer&MockObject $previewRenderer;

    private MailArchiveGateway&MockObject $archiveGateway;

    private MailTemplateGateway&MockObject $templateGateway;

    private MailLetterheadLoader&MockObject $letterheadLoader;

    private MailCockpitController $controller;

    private string $orderId;

    protected function setUp(): void
    {
        $this->sender = $this->createMock(HugMailSender::class);
        $this->contextBuilder = $this->createMock(MailContextBuilder::class);
        $this->previewRenderer = $this->createMock(TemplatePreviewRenderer::class);
        $this->archiveGateway = $this->createMock(MailArchiveGateway::class);
        $this->templateGateway = $this->createMock(MailTemplateGateway::class);
        $this->letterheadLoader = $this->createMock(MailLetterheadLoader::class);

        $this->controller = new MailCockpitController(
            $this->sender,
            $this->contextBuilder,
            $this->previewRenderer,
            new TwigContentPolicy(),
            $this->archiveGateway,
            $this->templateGateway,
            $this->letterheadLoader,
        );

        $this->orderId = Uuid::randomHex();
    }

    /**
     * @param list<string> $privileges
     */
    private function contextWithPrivileges(array $privileges): Context
    {
        $source = new AdminApiSource(Uuid::randomHex());
        $source->setPermissions($privileges);

        return new Context($source);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonRequest(array $payload): Request
    {
        return new Request(content: (string) json_encode($payload));
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function sendPayload(array $overrides = []): array
    {
        return array_merge([
            'orderId' => $this->orderId,
            'recipients' => ['max@example.com' => 'Max Mustermann'],
            'subject' => 'Hello',
            'contentHtml' => '<p>Hello</p>',
        ], $overrides);
    }

    public function testSendRequiresFreeSenderPrivilegeForFreeMails(): void
    {
        $this->sender->expects(static::never())->method('send');

        $this->expectException(MissingPrivilegeException::class);

        $this->controller->send(
            $this->jsonRequest($this->sendPayload()),
            $this->contextWithPrivileges(['hug_mail_cockpit.sender']),
        );
    }

    public function testSendRequiresSenderPrivilegeForDocumentMails(): void
    {
        $this->sender->expects(static::never())->method('send');

        $this->expectException(MissingPrivilegeException::class);

        $this->controller->send(
            $this->jsonRequest($this->sendPayload([
                'source' => MailReferenceDefinition::SOURCE_DOCUMENT,
                'documentIds' => [Uuid::randomHex()],
            ])),
            $this->contextWithPrivileges(['hug_mail_cockpit.free_sender']),
        );
    }

    public function testSendRejectsTwigContentWithoutTwigEditorPrivilege(): void
    {
        $this->sender->expects(static::never())->method('send');

        $this->expectException(MailCockpitException::class);
        $this->expectExceptionMessageMatches('/twig_editor/');

        $this->controller->send(
            $this->jsonRequest($this->sendPayload([
                'contentHtml' => '{% if order %}x{% endif %}',
            ])),
            $this->contextWithPrivileges(['hug_mail_cockpit.free_sender']),
        );
    }

    public function testSendAllowsTwigContentWithTwigEditorPrivilege(): void
    {
        $this->sender->expects(static::once())->method('send');

        $response = $this->controller->send(
            $this->jsonRequest($this->sendPayload([
                'contentHtml' => '{% if order %}x{% endif %}',
            ])),
            $this->contextWithPrivileges(['hug_mail_cockpit.free_sender', 'hug_mail_cockpit.twig_editor', 'order:read']),
        );

        static::assertSame(204, $response->getStatusCode());
    }

    public function testSendBuildsCommandFromPayload(): void
    {
        $documentId = Uuid::randomHex();
        $templateId = Uuid::randomHex();

        $captured = null;
        $this->sender->expects(static::once())
            ->method('send')
            ->willReturnCallback(function (SendMailCommand $command) use (&$captured): void {
                $captured = $command;
            });

        $context = $this->contextWithPrivileges(['hug_mail_cockpit.sender', 'order:read']);

        $response = $this->controller->send(
            $this->jsonRequest([
                'orderId' => $this->orderId,
                'recipients' => ['max@example.com' => 'Max'],
                'cc' => ['cc@example.com' => 'CC'],
                'bcc' => ['bcc@example.com' => 'BCC'],
                'subject' => 'Invoice {{ order.orderNumber }}',
                'contentHtml' => '<p>See attachment</p>',
                'mailTemplateId' => $templateId,
                'documentIds' => [$documentId],
                'source' => MailReferenceDefinition::SOURCE_DOCUMENT,
            ]),
            $context,
        );

        static::assertSame(204, $response->getStatusCode());
        static::assertInstanceOf(SendMailCommand::class, $captured);
        static::assertSame($this->orderId, $captured->orderId);
        static::assertNull($captured->customerId);
        static::assertSame(['max@example.com' => 'Max'], $captured->recipients);
        static::assertSame(['cc@example.com' => 'CC'], $captured->cc);
        static::assertSame(['bcc@example.com' => 'BCC'], $captured->bcc);
        static::assertSame('Invoice {{ order.orderNumber }}', $captured->subject);
        static::assertSame($templateId, $captured->mailTemplateId);
        static::assertSame([$documentId], $captured->documentIds);
        static::assertSame(MailReferenceDefinition::SOURCE_DOCUMENT, $captured->source);
    }

    public function testPreviewRendersAgainstOrderContext(): void
    {
        $context = $this->contextWithPrivileges(['hug_mail_cockpit.free_sender', 'order:read']);

        $order = new OrderEntity();
        $mailContext = new MailContext(
            templateData: ['order' => $order],
            context: $context,
            salesChannelId: null,
            languageId: Uuid::randomHex(),
            recipientEmail: null,
            recipientName: null,
        );

        $this->contextBuilder->expects(static::once())
            ->method('buildOrderContext')
            ->with($this->orderId, $context)
            ->willReturn($mailContext);

        $this->letterheadLoader->method('getLetterhead')
            ->willReturn(['headerHtml' => null, 'footerHtml' => null]);

        $this->previewRenderer->expects(static::once())
            ->method('render')
            ->with('Subject', '<p>Body</p>', ['order' => $order], $mailContext->context, null, null)
            ->willReturn(new PreviewResult('Subject', '<p>Body</p>', []));

        $response = $this->controller->preview(
            $this->jsonRequest([
                'orderId' => $this->orderId,
                'subject' => 'Subject',
                'contentHtml' => '<p>Body</p>',
            ]),
            $context,
        );

        $body = json_decode((string) $response->getContent(), true);
        static::assertIsArray($body);
        static::assertSame('Subject', $body['subject']);
        static::assertSame('<p>Body</p>', $body['contentHtml']);
        static::assertSame([], $body['errors']);
    }

    public function testPreviewRequiresPrivilege(): void
    {
        $this->expectException(MissingPrivilegeException::class);

        $this->controller->preview(
            $this->jsonRequest(['orderId' => $this->orderId, 'subject' => '', 'contentHtml' => '']),
            $this->contextWithPrivileges(['hug_mail_cockpit.viewer']),
        );
    }

    public function testPreviewRejectsTwigContentWithoutTwigEditorPrivilege(): void
    {
        $this->expectException(MailCockpitException::class);

        $this->controller->preview(
            $this->jsonRequest([
                'orderId' => $this->orderId,
                'subject' => '{{ dump(order) }}',
                'contentHtml' => '',
            ]),
            $this->contextWithPrivileges(['hug_mail_cockpit.free_sender']),
        );
    }

    public function testVariablesReturnsPickerDataAndPrefill(): void
    {
        $context = $this->contextWithPrivileges(['hug_mail_cockpit.free_sender', 'order:read']);
        $languageId = Uuid::randomHex();

        $mailContext = new MailContext(
            templateData: ['order' => new OrderEntity()],
            context: $context,
            salesChannelId: null,
            languageId: $languageId,
            recipientEmail: 'max@example.com',
            recipientName: 'Max',
        );

        $this->contextBuilder->method('buildOrderContext')->willReturn($mailContext);
        $this->contextBuilder->method('getVariables')
            ->with($mailContext)
            ->willReturn(['order' => ['orderNumber', 'amountTotal']]);

        $response = $this->controller->variables(
            new Request(['orderId' => $this->orderId]),
            $context,
        );

        $body = json_decode((string) $response->getContent(), true);
        static::assertIsArray($body);
        static::assertSame(['order' => ['orderNumber', 'amountTotal']], $body['variables']);
        static::assertSame($languageId, $body['languageId']);
        static::assertSame('max@example.com', $body['recipientEmail']);
        static::assertSame('Max', $body['recipientName']);
    }

    public function testHistoryDelegatesToGateway(): void
    {
        $rows = [['id' => Uuid::randomHex(), 'subject' => 'S']];
        $context = $this->contextWithPrivileges(['hug_mail_cockpit.viewer', 'order:read']);

        $this->archiveGateway->expects(static::once())
            ->method('getHistory')
            ->with($this->orderId, null, $context)
            ->willReturn($rows);

        $response = $this->controller->history(new Request(['orderId' => $this->orderId]), $context);

        $body = json_decode((string) $response->getContent(), true);
        static::assertIsArray($body);
        static::assertSame($rows, $body['entries']);
        static::assertSame(1, $body['total']);
    }

    public function testRenderTemplateRendersDatabaseTemplateWithoutTwigPolicy(): void
    {
        $context = $this->contextWithPrivileges(['hug_mail_cockpit.free_sender', 'order:read']);
        $templateId = Uuid::randomHex();

        $order = new OrderEntity();
        $mailContext = new MailContext(
            templateData: ['order' => $order],
            context: $context,
            salesChannelId: null,
            languageId: Uuid::randomHex(),
            recipientEmail: null,
            recipientName: null,
        );

        $this->contextBuilder->method('buildOrderContext')->willReturn($mailContext);
        $this->letterheadLoader->method('getLetterhead')
            ->willReturn(['headerHtml' => null, 'footerHtml' => null]);

        // Template content comes from the database and may contain arbitrary
        // Twig — no twig_editor privilege must be required to render it.
        $this->templateGateway->expects(static::once())
            ->method('getTemplateContent')
            ->with($templateId, $mailContext->context)
            ->willReturn([
                'subject' => 'Order {% if true %}{{ order.orderNumber }}{% endif %}',
                'contentHtml' => '<p>{{ order.orderNumber }}</p>',
            ]);

        $this->previewRenderer->expects(static::once())
            ->method('render')
            ->willReturn(new PreviewResult('Order 10001', '<p>10001</p>', []));

        $response = $this->controller->renderTemplate(
            $this->jsonRequest([
                'orderId' => $this->orderId,
                'mailTemplateId' => $templateId,
            ]),
            $context,
        );

        $body = json_decode((string) $response->getContent(), true);
        static::assertIsArray($body);
        static::assertSame('Order 10001', $body['subject']);
        static::assertSame('<p>10001</p>', $body['contentHtml']);
        static::assertSame([], $body['errors']);
    }

    public function testRenderTemplateRequiresPrivilege(): void
    {
        $this->expectException(MissingPrivilegeException::class);

        $this->controller->renderTemplate(
            $this->jsonRequest(['orderId' => $this->orderId, 'mailTemplateId' => Uuid::randomHex()]),
            $this->contextWithPrivileges(['hug_mail_cockpit.viewer']),
        );
    }

    public function testPreviewPassesLetterheadToRenderer(): void
    {
        $context = $this->contextWithPrivileges(['hug_mail_cockpit.free_sender', 'order:read']);
        $salesChannelId = Uuid::randomHex();

        $mailContext = new MailContext(
            templateData: [],
            context: $context,
            salesChannelId: $salesChannelId,
            languageId: Uuid::randomHex(),
            recipientEmail: null,
            recipientName: null,
        );

        $this->contextBuilder->method('buildOrderContext')->willReturn($mailContext);
        $this->letterheadLoader->expects(static::once())
            ->method('getLetterhead')
            ->with($salesChannelId, $mailContext->context)
            ->willReturn(['headerHtml' => '<div>H</div>', 'footerHtml' => '<div>F</div>']);

        $this->previewRenderer->expects(static::once())
            ->method('render')
            ->with('S', '<p>B</p>', [], $mailContext->context, '<div>H</div>', '<div>F</div>')
            ->willReturn(new PreviewResult('S', '<div>H</div><p>B</p><div>F</div>', []));

        $this->controller->preview(
            $this->jsonRequest(['orderId' => $this->orderId, 'subject' => 'S', 'contentHtml' => '<p>B</p>']),
            $context,
        );
    }

    public function testSendRejectsMalformedRecipients(): void
    {
        $this->sender->expects(static::never())->method('send');

        $this->expectException(MailCockpitException::class);

        $this->controller->send(
            $this->jsonRequest($this->sendPayload([
                'recipients' => 'max@example.com',
            ])),
            $this->contextWithPrivileges(['hug_mail_cockpit.free_sender']),
        );
    }

    /**
     * The required privilege must derive from the actual payload, never from the
     * client-set `source` label: a `sender` without documents is a free mail and
     * must be rejected without `free_sender`, even when it claims source=document.
     */
    public function testSendWithoutDocumentsRequiresFreeSenderEvenWhenSourceClaimsDocument(): void
    {
        $this->sender->expects(static::never())->method('send');

        $this->expectException(MissingPrivilegeException::class);

        $this->controller->send(
            $this->jsonRequest($this->sendPayload([
                'source' => MailReferenceDefinition::SOURCE_DOCUMENT,
                'documentIds' => [],
                'contentHtml' => '<p>Arbitrary free text</p>',
            ])),
            $this->contextWithPrivileges(['hug_mail_cockpit.sender', 'order:read']),
        );
    }

    public function testSendRequiresOrderReadCapability(): void
    {
        $this->sender->expects(static::never())->method('send');

        $this->expectException(MissingPrivilegeException::class);

        // free_sender may compose, but reading the order requires order:read.
        $this->controller->send(
            $this->jsonRequest($this->sendPayload()),
            $this->contextWithPrivileges(['hug_mail_cockpit.free_sender']),
        );
    }

    public function testVariablesRequireOrderReadCapability(): void
    {
        $this->contextBuilder->expects(static::never())->method('buildOrderContext');

        $this->expectException(MissingPrivilegeException::class);

        $this->controller->variables(
            new Request(['orderId' => $this->orderId]),
            $this->contextWithPrivileges(['hug_mail_cockpit.free_sender']),
        );
    }

    public function testHistoryRequiresOrderReadCapability(): void
    {
        $this->archiveGateway->expects(static::never())->method('getHistory');

        $this->expectException(MissingPrivilegeException::class);

        $this->controller->history(
            new Request(['orderId' => $this->orderId]),
            $this->contextWithPrivileges(['hug_mail_cockpit.viewer']),
        );
    }
}
