<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Service;

use Hug\MailCockpit\Exception\MailCockpitException;
use Hug\MailCockpit\Service\MailArchiveGateway;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;

class MailArchiveGatewayTest extends TestCase
{
    public function testNotAvailableWithoutMailArchivePlugin(): void
    {
        $gateway = new MailArchiveGateway(null);

        static::assertFalse($gateway->isAvailable());
    }

    public function testHistoryThrowsWithoutMailArchivePlugin(): void
    {
        $gateway = new MailArchiveGateway(null);

        $this->expectException(MailCockpitException::class);

        $gateway->getHistory(Uuid::randomHex(), null, new Context(new SystemSource()));
    }

    public function testHistoryThrowsWithoutTarget(): void
    {
        $gateway = new MailArchiveGateway(new StaticEntityRepository([]));

        $this->expectException(MailCockpitException::class);

        $gateway->getHistory(null, null, new Context(new SystemSource()));
    }

    public function testHistoryFiltersByOrderIdAndMapsRows(): void
    {
        $orderId = Uuid::randomHex();
        $archiveId = Uuid::randomHex();
        $templateId = Uuid::randomHex();
        $createdAt = new \DateTimeImmutable('2026-07-01 10:00:00');

        $entry = new ArrayEntity([
            'id' => $archiveId,
            'subject' => 'Your invoice',
            'sender' => ['shop@example.com' => 'Shop'],
            'receiver' => ['max@example.com' => 'Max'],
            'transportState' => 'sent',
            'mailTemplateId' => $templateId,
            'htmlText' => '<p>Mail body</p>',
            'createdAt' => $createdAt,
        ]);
        $entry->setUniqueIdentifier($archiveId);

        $capturedCriteria = null;
        $repository = new StaticEntityRepository([
            function (Criteria $criteria) use (&$capturedCriteria, $entry): array {
                $capturedCriteria = $criteria;

                return [$entry];
            },
        ]);

        $gateway = new MailArchiveGateway($repository);
        $rows = $gateway->getHistory($orderId, null, new Context(new SystemSource()));

        static::assertInstanceOf(Criteria::class, $capturedCriteria);
        $filters = $capturedCriteria->getFilters();
        static::assertCount(1, $filters);
        static::assertInstanceOf(EqualsFilter::class, $filters[0]);
        static::assertSame('orderId', $filters[0]->getField());
        static::assertSame($orderId, $filters[0]->getValue());

        static::assertCount(1, $rows);
        static::assertSame($archiveId, $rows[0]['id']);
        static::assertSame('Your invoice', $rows[0]['subject']);
        static::assertSame(['max@example.com' => 'Max'], $rows[0]['receiver']);
        static::assertSame('sent', $rows[0]['transportState']);
        static::assertSame($templateId, $rows[0]['mailTemplateId']);
        static::assertSame('<p>Mail body</p>', $rows[0]['htmlText']);
        static::assertSame($createdAt->format(\DateTimeInterface::ATOM), $rows[0]['createdAt']);
    }

    public function testHistoryFiltersByCustomerId(): void
    {
        $customerId = Uuid::randomHex();

        $capturedCriteria = null;
        $repository = new StaticEntityRepository([
            function (Criteria $criteria) use (&$capturedCriteria): array {
                $capturedCriteria = $criteria;

                return [];
            },
        ]);

        $gateway = new MailArchiveGateway($repository);
        $rows = $gateway->getHistory(null, $customerId, new Context(new SystemSource()));

        static::assertSame([], $rows);
        static::assertInstanceOf(Criteria::class, $capturedCriteria);
        $filters = $capturedCriteria->getFilters();
        static::assertInstanceOf(EqualsFilter::class, $filters[0]);
        static::assertSame('customerId', $filters[0]->getField());
    }
}
