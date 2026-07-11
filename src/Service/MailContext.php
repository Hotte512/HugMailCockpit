<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

use Shopware\Core\Framework\Context;

/**
 * Everything the mail dispatch needs about its target: the Twig template data,
 * a Context whose language chain starts with the order/customer language
 * (never the admin language), and recipient prefill data for the compose modal.
 */
final readonly class MailContext
{
    /**
     * @param array<string, mixed> $templateData
     */
    public function __construct(
        public array $templateData,
        public Context $context,
        public ?string $salesChannelId,
        public string $languageId,
        public ?string $recipientEmail,
        public ?string $recipientName,
    ) {
    }
}
