<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

use Hug\MailCockpit\Exception\MailCockpitException;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Framework\Context;

/**
 * Single dispatch path for all cockpit mails (guardrail: always through
 * AbstractMailService, never MailService directly wired elsewhere).
 */
class HugMailSender
{
    public function __construct(
        private readonly AbstractMailService $mailService,
        private readonly MailContextBuilder $contextBuilder,
        private readonly AttachmentResolver $attachmentResolver,
        private readonly MailReferenceWriter $referenceWriter,
    ) {
    }

    public function send(SendMailCommand $command, Context $context): void
    {
        if ($command->recipients === []) {
            throw MailCockpitException::missingRecipients();
        }

        $mailContext = $this->buildMailContext($command, $context);

        $data = [
            'recipients' => $command->recipients,
            'subject' => $command->subject,
            'contentHtml' => $command->contentHtml,
            'contentPlain' => $this->htmlToPlainText($command->contentHtml),
            'salesChannelId' => $mailContext->salesChannelId,
        ];

        if ($command->documentIds !== []) {
            $data['binAttachments'] = $this->attachmentResolver->resolveDocuments(
                $command->documentIds,
                $mailContext->context,
            );
        }

        if ($command->mediaIds !== []) {
            $data['mediaIds'] = $command->mediaIds;
        }

        if ($command->cc !== []) {
            $data['recipientsCc'] = $command->cc;
        }

        if ($command->bcc !== []) {
            $data['recipientsBcc'] = $command->bcc;
        }

        // MailArchive picks these up from $data (X-Frosh-* headers) and links
        // the archived mail to order/customer/template — see konzept.md §1.
        if ($command->orderId !== null) {
            $data['orderId'] = $command->orderId;
        }

        if ($command->customerId !== null) {
            $data['customerId'] = $command->customerId;
        }

        if ($command->mailTemplateId !== null) {
            $data['templateId'] = $command->mailTemplateId;
        }

        $email = $this->mailService->send($data, $mailContext->context, $mailContext->templateData);

        if ($email === null) {
            throw MailCockpitException::mailDispatchFailed();
        }

        $this->referenceWriter->write($command->source, $command->orderId, $command->documentIds, $context);
    }

    private function buildMailContext(SendMailCommand $command, Context $context): MailContext
    {
        if ($command->orderId !== null) {
            return $this->contextBuilder->buildOrderContext($command->orderId, $context);
        }

        if ($command->customerId !== null) {
            return $this->contextBuilder->buildCustomerContext($command->customerId, $context);
        }

        throw MailCockpitException::missingTarget();
    }

    private function htmlToPlainText(string $html): string
    {
        $text = (string) preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = (string) preg_replace('/<\/(p|div|h[1-6]|li|tr)>/i', "\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/[ \t]+/', ' ', $text);

        return trim($text);
    }
}
