<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Service;

use Hug\MailCockpit\Core\Content\MailReference\MailReferenceDefinition;
use Hug\MailCockpit\Exception\MailCockpitException;
use Hug\MailCockpit\Service\AttachmentResolver;
use Hug\MailCockpit\Service\HugMailSender;
use Hug\MailCockpit\Service\MailContext;
use Hug\MailCockpit\Service\MailContextBuilder;
use Hug\MailCockpit\Service\MailReferenceWriter;
use Hug\MailCockpit\Service\SendMailCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Mime\Email;

class HugMailSenderTest extends TestCase
{
    private string $orderId;

    private string $languageId;

    private string $salesChannelId;

    private AbstractMailService&MockObject $mailService;

    private MailContextBuilder&MockObject $contextBuilder;

    private AttachmentResolver&MockObject $attachmentResolver;

    private MailReferenceWriter&MockObject $referenceWriter;

    private Context $adminContext;

    private MailContext $mailContext;

    protected function setUp(): void
    {
        $this->orderId = Uuid::randomHex();
        $this->languageId = Uuid::randomHex();
        $this->salesChannelId = Uuid::randomHex();

        $this->mailService = $this->createMock(AbstractMailService::class);
        $this->contextBuilder = $this->createMock(MailContextBuilder::class);
        $this->attachmentResolver = $this->createMock(AttachmentResolver::class);
        $this->referenceWriter = $this->createMock(MailReferenceWriter::class);

        $this->adminContext = new Context(new AdminApiSource(Uuid::randomHex()));

        $order = new OrderEntity();
        $order->setOrderNumber('10001');

        $this->mailContext = new MailContext(
            templateData: ['order' => $order],
            context: new Context(
                $this->adminContext->getSource(),
                [],
                Uuid::randomHex(),
                [$this->languageId, ...$this->adminContext->getLanguageIdChain()],
            ),
            salesChannelId: $this->salesChannelId,
            languageId: $this->languageId,
            recipientEmail: 'max@example.com',
            recipientName: 'Max Mustermann',
        );
    }

    private function sender(): HugMailSender
    {
        $systemConfigService = $this->createMock(SystemConfigService::class);
        $systemConfigService->method('getString')
            ->with('core.basicInformation.shopName', $this->salesChannelId)
            ->willReturn('Demo Testshop');

        return new HugMailSender(
            $this->mailService,
            $this->contextBuilder,
            $this->attachmentResolver,
            $this->referenceWriter,
            $systemConfigService,
        );
    }

    public function testSendsOrderMailWithOrderLanguageContextAndArchiveKeys(): void
    {
        $this->contextBuilder->expects(static::once())
            ->method('buildOrderContext')
            ->with($this->orderId, $this->adminContext)
            ->willReturn($this->mailContext);

        $capturedData = null;
        $capturedContext = null;
        $capturedTemplateData = null;
        $this->mailService->expects(static::once())
            ->method('send')
            ->willReturnCallback(function (array $data, Context $context, array $templateData) use (&$capturedData, &$capturedContext, &$capturedTemplateData): \Symfony\Component\Mime\Email {
                $capturedData = $data;
                $capturedContext = $context;
                $capturedTemplateData = $templateData;

                return new Email();
            });

        $command = new SendMailCommand(
            orderId: $this->orderId,
            customerId: null,
            recipients: ['max@example.com' => 'Max Mustermann'],
            subject: 'Order {{ order.orderNumber }}',
            contentHtml: '<p>Hello</p>',
        );

        $this->sender()->send($command, $this->adminContext);

        static::assertIsArray($capturedData);
        static::assertSame(['max@example.com' => 'Max Mustermann'], $capturedData['recipients']);
        static::assertSame('Order {{ order.orderNumber }}', $capturedData['subject']);
        static::assertSame('<p>Hello</p>', $capturedData['contentHtml']);
        static::assertSame($this->salesChannelId, $capturedData['salesChannelId']);
        // MailService reads $data['senderName'] unguarded — the key is mandatory.
        static::assertSame('Demo Testshop', $capturedData['senderName']);
        // MailArchive links the mail via these data keys (X-Frosh-* headers).
        static::assertSame($this->orderId, $capturedData['orderId']);
        // The mail must render in the order language, never the admin language.
        static::assertInstanceOf(Context::class, $capturedContext);
        static::assertSame($this->languageId, $capturedContext->getLanguageIdChain()[0]);
        static::assertSame($this->mailContext->templateData, $capturedTemplateData);
    }

    public function testGeneratesPlainTextFromHtmlContent(): void
    {
        $this->contextBuilder->method('buildOrderContext')->willReturn($this->mailContext);

        $capturedData = null;
        $this->mailService->method('send')
            ->willReturnCallback(function (array $data) use (&$capturedData): \Symfony\Component\Mime\Email {
                $capturedData = $data;

                return new Email();
            });

        $command = new SendMailCommand(
            orderId: $this->orderId,
            customerId: null,
            recipients: ['max@example.com' => 'Max'],
            subject: 'S',
            contentHtml: "<h1>Hello Max,</h1><p>your order<br>has &amp; shipped.</p>",
        );

        $this->sender()->send($command, $this->adminContext);

        static::assertIsArray($capturedData);
        static::assertIsString($capturedData['contentPlain']);
        static::assertStringNotContainsString('<', $capturedData['contentPlain']);
        static::assertStringContainsString('Hello Max,', $capturedData['contentPlain']);
        static::assertStringContainsString("your order\nhas & shipped.", $capturedData['contentPlain']);
    }

    public function testResolvesDocumentsAndPassesUploadedMediaIds(): void
    {
        $documentId = Uuid::randomHex();
        $mediaId = Uuid::randomHex();
        $binAttachments = [['content' => '%PDF', 'fileName' => 'invoice.pdf', 'mimeType' => 'application/pdf']];

        $this->contextBuilder->method('buildOrderContext')->willReturn($this->mailContext);
        $this->attachmentResolver->expects(static::once())
            ->method('resolveDocuments')
            ->with([$documentId], $this->mailContext->context)
            ->willReturn($binAttachments);

        $capturedData = null;
        $this->mailService->method('send')
            ->willReturnCallback(function (array $data) use (&$capturedData): \Symfony\Component\Mime\Email {
                $capturedData = $data;

                return new Email();
            });

        $command = new SendMailCommand(
            orderId: $this->orderId,
            customerId: null,
            recipients: ['max@example.com' => 'Max'],
            subject: 'S',
            contentHtml: '<p>x</p>',
            documentIds: [$documentId],
            mediaIds: [$mediaId],
            source: MailReferenceDefinition::SOURCE_DOCUMENT,
        );

        $this->sender()->send($command, $this->adminContext);

        static::assertIsArray($capturedData);
        static::assertSame($binAttachments, $capturedData['binAttachments']);
        static::assertSame([$mediaId], $capturedData['mediaIds']);
    }

    public function testWritesReferenceAfterSuccessfulSend(): void
    {
        $documentId = Uuid::randomHex();

        $this->contextBuilder->method('buildOrderContext')->willReturn($this->mailContext);
        $this->mailService->method('send')->willReturn(new Email());

        $this->referenceWriter->expects(static::once())
            ->method('write')
            ->with(MailReferenceDefinition::SOURCE_DOCUMENT, $this->orderId, [$documentId], $this->adminContext);

        $command = new SendMailCommand(
            orderId: $this->orderId,
            customerId: null,
            recipients: ['max@example.com' => 'Max'],
            subject: 'S',
            contentHtml: '<p>x</p>',
            documentIds: [$documentId],
            source: MailReferenceDefinition::SOURCE_DOCUMENT,
        );

        $this->sender()->send($command, $this->adminContext);
    }

    public function testDoesNotWriteReferenceWhenSendFails(): void
    {
        $this->contextBuilder->method('buildOrderContext')->willReturn($this->mailContext);
        $this->mailService->method('send')->willReturn(null);
        $this->referenceWriter->expects(static::never())->method('write');

        $command = new SendMailCommand(
            orderId: $this->orderId,
            customerId: null,
            recipients: ['max@example.com' => 'Max'],
            subject: 'S',
            contentHtml: '<p>x</p>',
        );

        $this->expectException(MailCockpitException::class);

        $this->sender()->send($command, $this->adminContext);
    }

    public function testCustomerMailUsesCustomerContextAndArchiveKey(): void
    {
        $customerId = Uuid::randomHex();

        $customerContext = new MailContext(
            templateData: ['customer' => new OrderEntity()],
            context: $this->mailContext->context,
            salesChannelId: $this->salesChannelId,
            languageId: $this->languageId,
            recipientEmail: 'erika@example.com',
            recipientName: 'Erika',
        );

        $this->contextBuilder->expects(static::once())
            ->method('buildCustomerContext')
            ->with($customerId, $this->adminContext)
            ->willReturn($customerContext);
        $this->contextBuilder->expects(static::never())->method('buildOrderContext');

        $capturedData = null;
        $this->mailService->method('send')
            ->willReturnCallback(function (array $data) use (&$capturedData): \Symfony\Component\Mime\Email {
                $capturedData = $data;

                return new Email();
            });

        $command = new SendMailCommand(
            orderId: null,
            customerId: $customerId,
            recipients: ['erika@example.com' => 'Erika'],
            subject: 'S',
            contentHtml: '<p>x</p>',
        );

        $this->sender()->send($command, $this->adminContext);

        static::assertIsArray($capturedData);
        static::assertSame($customerId, $capturedData['customerId']);
        static::assertArrayNotHasKey('orderId', $capturedData);
    }

    public function testRejectsCommandWithoutTarget(): void
    {
        $command = new SendMailCommand(
            orderId: null,
            customerId: null,
            recipients: ['max@example.com' => 'Max'],
            subject: 'S',
            contentHtml: '<p>x</p>',
        );

        $this->expectException(MailCockpitException::class);

        $this->sender()->send($command, $this->adminContext);
    }

    public function testRejectsEmptyRecipients(): void
    {
        $command = new SendMailCommand(
            orderId: $this->orderId,
            customerId: null,
            recipients: [],
            subject: 'S',
            contentHtml: '<p>x</p>',
        );

        $this->expectException(MailCockpitException::class);

        $this->sender()->send($command, $this->adminContext);
    }

    public function testPassesCcBccAndTemplateIdWhenProvided(): void
    {
        $templateId = Uuid::randomHex();

        $this->contextBuilder->method('buildOrderContext')->willReturn($this->mailContext);

        $capturedData = null;
        $this->mailService->method('send')
            ->willReturnCallback(function (array $data) use (&$capturedData): \Symfony\Component\Mime\Email {
                $capturedData = $data;

                return new Email();
            });

        $command = new SendMailCommand(
            orderId: $this->orderId,
            customerId: null,
            recipients: ['max@example.com' => 'Max'],
            subject: 'S',
            contentHtml: '<p>x</p>',
            cc: ['cc@example.com' => 'CC'],
            bcc: ['bcc@example.com' => 'BCC'],
            mailTemplateId: $templateId,
        );

        $this->sender()->send($command, $this->adminContext);

        static::assertIsArray($capturedData);
        static::assertSame(['cc@example.com' => 'CC'], $capturedData['recipientsCc']);
        static::assertSame(['bcc@example.com' => 'BCC'], $capturedData['recipientsBcc']);
        static::assertSame($templateId, $capturedData['templateId']);
    }
}
