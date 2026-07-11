<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

use Hug\MailCockpit\Exception\MailCockpitException;
use Shopware\Core\Content\MailTemplate\MailTemplateCollection;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 * Loads mail template content for the "render, then edit" compose flow.
 * Always called with the mail language context so the template copy matches
 * the order/customer language — never the admin language.
 */
class MailTemplateGateway
{
    /**
     * @param EntityRepository<MailTemplateCollection> $mailTemplateRepository
     */
    public function __construct(
        private readonly EntityRepository $mailTemplateRepository,
    ) {
    }

    /**
     * @return array{subject: string, contentHtml: string}
     */
    public function getTemplateContent(string $mailTemplateId, Context $mailLanguageContext): array
    {
        $template = $this->mailTemplateRepository
            ->search(new Criteria([$mailTemplateId]), $mailLanguageContext)
            ->first();

        if (!$template instanceof MailTemplateEntity) {
            throw MailCockpitException::mailTemplateNotFound($mailTemplateId);
        }

        $translated = $template->getTranslated();
        $subject = $translated['subject'] ?? $template->getSubject();
        $contentHtml = $translated['contentHtml'] ?? $template->getContentHtml();

        return [
            'subject' => \is_string($subject) ? $subject : '',
            'contentHtml' => \is_string($contentHtml) ? $contentHtml : '',
        ];
    }
}
