<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1783771084CreateHugMailReferenceTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1783771084;
    }

    public function update(Connection $connection): void
    {
        // Deliberately no foreign key constraints: the audit trail must survive
        // order/document deletion, and frosh_mail_archive is an optional plugin.
        $connection->executeStatement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS `hug_mail_reference` (
                `id` BINARY(16) NOT NULL,
                `mail_archive_id` BINARY(16) NULL,
                `order_id` BINARY(16) NULL,
                `order_version_id` BINARY(16) NULL,
                `document_id` BINARY(16) NULL,
                `source` VARCHAR(32) NOT NULL,
                `sent_by_user_id` BINARY(16) NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                INDEX `idx.hug_mail_reference.order` (`order_id`, `order_version_id`),
                INDEX `idx.hug_mail_reference.mail_archive` (`mail_archive_id`)
            )
                ENGINE = InnoDB
                DEFAULT CHARSET = utf8mb4
                COLLATE = utf8mb4_unicode_ci;
            SQL);
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}
