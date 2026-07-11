<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Service;

use Hug\MailCockpit\Service\MailLetterheadLoader;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\MailTemplate\Aggregate\MailHeaderFooter\MailHeaderFooterEntity;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;

class MailLetterheadLoaderTest extends TestCase
{
    public function testReturnsTranslatedHeaderAndFooter(): void
    {
        $salesChannelId = Uuid::randomHex();

        $headerFooter = new MailHeaderFooterEntity();
        $headerFooter->setId(Uuid::randomHex());
        $headerFooter->setUniqueIdentifier($headerFooter->getId());
        $headerFooter->setTranslated([
            'headerHtml' => '<div>Header</div>',
            'footerHtml' => '<div>Footer</div>',
        ]);

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId($salesChannelId);
        $salesChannel->setUniqueIdentifier($salesChannelId);
        $salesChannel->setMailHeaderFooter($headerFooter);

        $loader = new MailLetterheadLoader(new StaticEntityRepository([[$salesChannel]]));
        $letterhead = $loader->getLetterhead($salesChannelId, new Context(new SystemSource()));

        static::assertSame('<div>Header</div>', $letterhead['headerHtml']);
        static::assertSame('<div>Footer</div>', $letterhead['footerHtml']);
    }

    public function testReturnsNullsWithoutAssignedLetterhead(): void
    {
        $salesChannelId = Uuid::randomHex();

        $salesChannel = new SalesChannelEntity();
        $salesChannel->setId($salesChannelId);
        $salesChannel->setUniqueIdentifier($salesChannelId);

        $loader = new MailLetterheadLoader(new StaticEntityRepository([[$salesChannel]]));
        $letterhead = $loader->getLetterhead($salesChannelId, new Context(new SystemSource()));

        static::assertNull($letterhead['headerHtml']);
        static::assertNull($letterhead['footerHtml']);
    }

    public function testReturnsNullsWithoutSalesChannelId(): void
    {
        $loader = new MailLetterheadLoader(new StaticEntityRepository([]));
        $letterhead = $loader->getLetterhead(null, new Context(new SystemSource()));

        static::assertNull($letterhead['headerHtml']);
        static::assertNull($letterhead['footerHtml']);
    }
}
