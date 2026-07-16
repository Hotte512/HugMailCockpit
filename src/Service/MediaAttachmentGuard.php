<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

use Hug\MailCockpit\Exception\MailCockpitException;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderCollection;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Ensures uploaded-media attachments are confined to the plugin's own upload
 * folder (konzept.md §6). Core's MailService loads media by id under the system
 * scope, enforcing no ownership — without this guard any sender could attach an
 * arbitrary media id (private folders, other customers' uploads) and exfiltrate
 * it to any recipient.
 */
class MediaAttachmentGuard
{
    private const ATTACHMENT_FOLDER_ENTITY = 'hug_mail_reference';

    /**
     * @param EntityRepository<MediaCollection> $mediaRepository
     * @param EntityRepository<MediaFolderCollection> $mediaFolderRepository
     */
    public function __construct(
        private readonly EntityRepository $mediaRepository,
        private readonly EntityRepository $mediaFolderRepository,
    ) {
    }

    /**
     * @param list<string> $mediaIds
     */
    public function assertAllowed(array $mediaIds, Context $context): void
    {
        if ($mediaIds === []) {
            return;
        }

        $folderId = $this->resolveAttachmentFolderId($context);

        if ($folderId === null) {
            // No plugin folder → nothing is a legitimate cockpit upload.
            throw MailCockpitException::mediaAttachmentNotAllowed($mediaIds[0]);
        }

        $criteria = new Criteria($mediaIds);
        $criteria->addFilter(new EqualsFilter('mediaFolderId', $folderId));

        $allowedIds = $this->mediaRepository->searchIds($criteria, $context)->getIds();

        foreach ($mediaIds as $mediaId) {
            if (!\in_array($mediaId, $allowedIds, true)) {
                throw MailCockpitException::mediaAttachmentNotAllowed($mediaId);
            }
        }
    }

    private function resolveAttachmentFolderId(Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('defaultFolder.entity', self::ATTACHMENT_FOLDER_ENTITY));

        $ids = $this->mediaFolderRepository->searchIds($criteria, $context)->getIds();

        $folderId = $ids[0] ?? null;

        return \is_string($folderId) ? $folderId : null;
    }
}
