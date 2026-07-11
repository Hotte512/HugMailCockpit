<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Service;

use Hug\MailCockpit\Core\Content\MailReference\MailReferenceDefinition;
use Hug\MailCockpit\Exception\MailCockpitException;
use Hug\MailCockpit\Service\MailReferenceWriter;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;

class MailReferenceWriterTest extends TestCase
{
    public function testWritesSingleReferenceForFreeMailWithSendingUser(): void
    {
        $orderId = Uuid::randomHex();
        $userId = Uuid::randomHex();
        $context = new Context(new AdminApiSource($userId));

        $repository = new StaticEntityRepository([]);
        $writer = new MailReferenceWriter($repository);

        $writer->write(MailReferenceDefinition::SOURCE_FREE, $orderId, [], $context);

        static::assertCount(1, $repository->creates);
        $rows = $repository->creates[0];
        static::assertCount(1, $rows);
        static::assertIsArray($rows[0]);
        static::assertSame(MailReferenceDefinition::SOURCE_FREE, $rows[0]['source']);
        static::assertSame($orderId, $rows[0]['orderId']);
        static::assertSame($userId, $rows[0]['sentByUserId']);
        static::assertArrayNotHasKey('documentId', $rows[0]);
    }

    public function testWritesOneReferencePerDocument(): void
    {
        $orderId = Uuid::randomHex();
        $documentIdA = Uuid::randomHex();
        $documentIdB = Uuid::randomHex();
        $context = new Context(new AdminApiSource(Uuid::randomHex()));

        $repository = new StaticEntityRepository([]);
        $writer = new MailReferenceWriter($repository);

        $writer->write(MailReferenceDefinition::SOURCE_DOCUMENT, $orderId, [$documentIdA, $documentIdB], $context);

        $rows = $repository->creates[0];
        static::assertCount(2, $rows);
        static::assertIsArray($rows[0]);
        static::assertIsArray($rows[1]);
        static::assertSame($documentIdA, $rows[0]['documentId']);
        static::assertSame($documentIdB, $rows[1]['documentId']);
        static::assertSame($orderId, $rows[0]['orderId']);
        static::assertSame($orderId, $rows[1]['orderId']);
    }

    public function testSentByUserIsNullForNonAdminSource(): void
    {
        $repository = new StaticEntityRepository([]);
        $writer = new MailReferenceWriter($repository);

        $writer->write(MailReferenceDefinition::SOURCE_FREE, null, [], new Context(new SystemSource()));

        $rows = $repository->creates[0];
        static::assertIsArray($rows[0]);
        static::assertNull($rows[0]['sentByUserId']);
        static::assertArrayNotHasKey('orderId', $rows[0]);
    }

    public function testRejectsUnknownSource(): void
    {
        $writer = new MailReferenceWriter(new StaticEntityRepository([]));

        $this->expectException(MailCockpitException::class);

        $writer->write('newsletter', null, [], new Context(new SystemSource()));
    }
}
