<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Hug\MailCockpit\Migration\Migration1783850540CreateHugMailTextSnippetTable;
use PHPUnit\Framework\TestCase;

class Migration1783850540CreateHugMailTextSnippetTableTest extends TestCase
{
    public function testCreationTimestampMatchesClassName(): void
    {
        $migration = new Migration1783850540CreateHugMailTextSnippetTable();

        static::assertSame(1783850540, $migration->getCreationTimestamp());
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

        $migration = new Migration1783850540CreateHugMailTextSnippetTable();
        $migration->update($connection);
        $migration->update($connection);

        static::assertCount(2, $executedSql);

        $sql = $executedSql[0];
        static::assertStringContainsString('CREATE TABLE IF NOT EXISTS `hug_mail_text_snippet`', $sql);

        foreach (['name', 'content', 'created_at', 'updated_at'] as $column) {
            static::assertStringContainsString("`{$column}`", $sql, sprintf('Column %s missing', $column));
        }

        static::assertStringNotContainsStringIgnoringCase('DROP', $sql);
    }

    public function testUpdateDestructiveIsANoOp(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::never())->method('executeStatement');

        $migration = new Migration1783850540CreateHugMailTextSnippetTable();
        $migration->updateDestructive($connection);
    }
}
