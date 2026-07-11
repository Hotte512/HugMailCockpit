<?php

declare(strict_types=1);

namespace Hug\MailCockpit;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class HugMailCockpit extends Plugin
{
    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        $this->upsertAttachmentMediaFolder($installContext->getContext());
    }

    public function update(UpdateContext $updateContext): void
    {
        parent::update($updateContext);

        $this->upsertAttachmentMediaFolder($updateContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->dropTables();
    }

    /**
     * Dedicated media folder for compose-modal uploads (konzept.md §6).
     * Registered as default folder for hug_mail_reference so
     * sw-media-upload-v2 resolves it via default-folder. Deliberately NOT a
     * private folder: mail attachments are fetched by media URL at dispatch
     * time and private URLs would not resolve.
     */
    private function upsertAttachmentMediaFolder(Context $context): void
    {
        $defaultFolderRepository = $this->container?->get('media_default_folder.repository');
        \assert($defaultFolderRepository instanceof EntityRepository);

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entity', 'hug_mail_reference'));

        if ($defaultFolderRepository->searchIds($criteria, $context)->getTotal() > 0) {
            return;
        }

        $defaultFolderRepository->create([
            [
                'entity' => 'hug_mail_reference',
                'associationFields' => [],
                'folder' => [
                    'name' => 'Mail-Cockpit Anhänge',
                    'useParentConfiguration' => false,
                    'configuration' => [],
                ],
            ],
        ], $context);
    }

    private function dropTables(): void
    {
        $connection = $this->container?->get(Connection::class);
        \assert($connection instanceof Connection);

        $connection->executeStatement('DROP TABLE IF EXISTS `hug_mail_reference`');
    }
}
