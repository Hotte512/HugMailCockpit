<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

use Hug\MailCockpit\Exception\MailCockpitException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

/**
 * Runtime guard + read access for FroshPlatformMailArchive (optional plugin,
 * konzept.md §1). The repository is injected with on-invalid="null", so this
 * class is the only place that knows whether MailArchive is available. No
 * class-level dependency on Frosh code — rows are mapped via Entity::getVars().
 */
class MailArchiveGateway
{
    private const HISTORY_LIMIT = 100;

    /**
     * @param EntityRepository<EntityCollection<Entity>>|null $mailArchiveRepository
     */
    public function __construct(
        private readonly ?EntityRepository $mailArchiveRepository,
    ) {
    }

    public function isAvailable(): bool
    {
        if ($this->mailArchiveRepository === null) {
            return false;
        }

        try {
            // mail_template_id exists since MailArchive 3.6 — our minimum version.
            return $this->mailArchiveRepository->getDefinition()->getFields()->get('mailTemplateId') !== null;
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getHistory(?string $orderId, ?string $customerId, Context $context): array
    {
        if ($this->mailArchiveRepository === null) {
            throw MailCockpitException::mailArchiveNotAvailable();
        }

        if ($orderId === null && $customerId === null) {
            throw MailCockpitException::missingTarget();
        }

        $criteria = new Criteria();
        $criteria->addFilter(
            $orderId !== null
                ? new EqualsFilter('orderId', $orderId)
                : new EqualsFilter('customerId', $customerId),
        );
        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));
        $criteria->addAssociation('attachments');
        $criteria->setLimit(self::HISTORY_LIMIT);

        $entries = $this->mailArchiveRepository->search($criteria, $context)->getEntities();

        $rows = [];
        foreach ($entries as $entry) {
            $vars = $entry->getVars();

            $createdAt = $vars['createdAt'] ?? null;
            $attachments = $vars['attachments'] ?? null;

            $rows[] = [
                'id' => $vars['id'] ?? $entry->getUniqueIdentifier(),
                'subject' => $vars['subject'] ?? null,
                'sender' => $vars['sender'] ?? null,
                'receiver' => $vars['receiver'] ?? null,
                'transportState' => $vars['transportState'] ?? null,
                'mailTemplateId' => $vars['mailTemplateId'] ?? null,
                'createdAt' => $createdAt instanceof \DateTimeInterface
                    ? $createdAt->format(\DateTimeInterface::ATOM)
                    : null,
                'attachmentCount' => is_countable($attachments) ? \count($attachments) : 0,
            ];
        }

        return $rows;
    }
}
