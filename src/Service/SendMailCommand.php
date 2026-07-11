<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

use Hug\MailCockpit\Core\Content\MailReference\MailReferenceDefinition;

final readonly class SendMailCommand
{
    /**
     * @param array<string, string> $recipients e-mail address => display name
     * @param array<string, string> $cc
     * @param array<string, string> $bcc
     * @param list<string> $documentIds generated documents to attach
     * @param list<string> $mediaIds uploaded media files to attach
     */
    public function __construct(
        public ?string $orderId,
        public ?string $customerId,
        public array $recipients,
        public string $subject,
        public string $contentHtml,
        public array $cc = [],
        public array $bcc = [],
        public ?string $mailTemplateId = null,
        public array $documentIds = [],
        public array $mediaIds = [],
        public string $source = MailReferenceDefinition::SOURCE_FREE,
    ) {
    }
}
