<?php

declare(strict_types=1);

namespace Hug\MailCockpit;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class HugMailCockpit extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->dropTables();
    }

    private function dropTables(): void
    {
        $connection = $this->container?->get(Connection::class);
        \assert($connection instanceof Connection);

        $connection->executeStatement('DROP TABLE IF EXISTS `hug_mail_reference`');
    }
}
