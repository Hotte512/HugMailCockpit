<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Hug\MailCockpit\Migration\Migration1783771084CreateHugMailReferenceTable;
use PHPUnit\Framework\TestCase;

class Migration1783771084CreateHugMailReferenceTableTest extends TestCase
{
    public function testCreationTimestampMatchesClassName(): void
    {
        $migration = new Migration1783771084CreateHugMailReferenceTable();

        static::assertSame(1783771084, $migration->getCreationTimestamp());
    }

    public function testUpdateCreatesTableIdempotently(): void
    {
        $executedSql = [];

        $connection = $this->createMock(Connection::class);
        $connection->method('executeStatement')
            ->willReturnCallback(static function (string $sql) use (&$executedSql): int {
                $executedSql[] = $sql;

                return 0;
            });

        $migration = new Migration1783771084CreateHugMailReferenceTable();
        $migration->update($connection);
        // Running twice must be safe (CREATE TABLE IF NOT EXISTS).
        $migration->update($connection);

        static::assertCount(2, $executedSql);

        $sql = $executedSql[0];
        static::assertStringContainsString('CREATE TABLE IF NOT EXISTS `hug_mail_reference`', $sql);

        foreach ([
            'mail_archive_id',
            'order_id',
            'order_version_id',
            'document_id',
            'source',
            'sent_by_user_id',
            'created_at',
            'updated_at',
        ] as $column) {
            static::assertStringContainsString("`{$column}`", $sql, sprintf('Column %s missing', $column));
        }

        // Audit layer: no hard foreign keys by design (konzept.md §1).
        static::assertStringNotContainsStringIgnoringCase('FOREIGN KEY', $sql);
        static::assertStringNotContainsStringIgnoringCase('DROP', $sql);
    }

    public function testUpdateDestructiveIsANoOp(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::never())->method('executeStatement');

        $migration = new Migration1783771084CreateHugMailReferenceTable();
        $migration->updateDestructive($connection);
    }
}
