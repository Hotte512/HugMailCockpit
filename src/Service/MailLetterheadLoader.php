<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;

/**
 * Letterhead (mail_header_footer) of a sales channel — used so the preview
 * shows exactly what MailService::buildContents() will wrap around the
 * content at send time.
 */
class MailLetterheadLoader
{
    /**
     * @param EntityRepository<SalesChannelCollection> $salesChannelRepository
     */
    public function __construct(
        private readonly EntityRepository $salesChannelRepository,
    ) {
    }

    /**
     * @return array{headerHtml: string|null, footerHtml: string|null}
     */
    public function getLetterhead(?string $salesChannelId, Context $context): array
    {
        $empty = ['headerHtml' => null, 'footerHtml' => null];

        if ($salesChannelId === null) {
            return $empty;
        }

        $criteria = new Criteria([$salesChannelId]);
        $criteria->addAssociation('mailHeaderFooter');

        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        if (!$salesChannel instanceof SalesChannelEntity) {
            return $empty;
        }

        $headerFooter = $salesChannel->getMailHeaderFooter();

        if ($headerFooter === null) {
            return $empty;
        }

        $translated = $headerFooter->getTranslated();
        $headerHtml = $translated['headerHtml'] ?? null;
        $footerHtml = $translated['footerHtml'] ?? null;

        return [
            'headerHtml' => \is_string($headerHtml) ? $headerHtml : null,
            'footerHtml' => \is_string($footerHtml) ? $footerHtml : null,
        ];
    }
}
