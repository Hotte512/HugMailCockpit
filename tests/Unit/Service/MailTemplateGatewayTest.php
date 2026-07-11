<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Service;

use Hug\MailCockpit\Exception\MailCockpitException;
use Hug\MailCockpit\Service\MailTemplateGateway;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;

class MailTemplateGatewayTest extends TestCase
{
    public function testLoadsTranslatedSubjectAndContent(): void
    {
        $templateId = Uuid::randomHex();

        $template = new MailTemplateEntity();
        $template->setId($templateId);
        $template->setUniqueIdentifier($templateId);
        $template->setTranslated([
            'subject' => 'Ihre Bestellung {{ order.orderNumber }}',
            'contentHtml' => '<p>Hallo {{ order.orderCustomer.firstName }}</p>',
            'contentPlain' => 'Hallo',
        ]);

        $capturedContext = null;
        $repository = new StaticEntityRepository([
            function ($criteria, Context $context) use (&$capturedContext, $template): array {
                $capturedContext = $context;

                return [$template];
            },
        ]);

        $mailLanguageContext = new Context(new SystemSource());

        $gateway = new MailTemplateGateway($repository);
        $result = $gateway->getTemplateContent($templateId, $mailLanguageContext);

        // The template must be loaded with the mail language context, never
        // the admin language.
        static::assertSame($mailLanguageContext, $capturedContext);
        static::assertSame('Ihre Bestellung {{ order.orderNumber }}', $result['subject']);
        static::assertSame('<p>Hallo {{ order.orderCustomer.firstName }}</p>', $result['contentHtml']);
    }

    public function testThrowsWhenTemplateNotFound(): void
    {
        $gateway = new MailTemplateGateway(new StaticEntityRepository([[]]));

        $this->expectException(MailCockpitException::class);

        $gateway->getTemplateContent(Uuid::randomHex(), new Context(new SystemSource()));
    }
}
