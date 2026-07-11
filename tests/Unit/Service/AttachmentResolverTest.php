<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Service;

use Hug\MailCockpit\Exception\MailCockpitException;
use Hug\MailCockpit\Service\AttachmentResolver;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Document\Renderer\RenderedDocument;
use Shopware\Core\Checkout\Document\Service\DocumentGenerator;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;

class AttachmentResolverTest extends TestCase
{
    public function testResolvesDocumentsToBinAttachmentArrays(): void
    {
        $documentIdA = Uuid::randomHex();
        $documentIdB = Uuid::randomHex();
        $context = new Context(new SystemSource());

        $documentA = new RenderedDocument(
            name: 'invoice_10001.pdf',
            contentType: 'application/pdf',
            content: '%PDF-A',
        );
        $documentB = new RenderedDocument(
            name: 'delivery_note_10001.pdf',
            contentType: 'application/pdf',
            content: '%PDF-B',
        );

        $generator = $this->createMock(DocumentGenerator::class);
        $generator->expects(static::exactly(2))
            ->method('readDocument')
            // fileType: null keeps the stored file extension (core pattern in
            // MailAttachmentsBuilder::mappingAttachments()).
            ->willReturnCallback(function (string $id, Context $c, string $deepLink, ?string $fileType) use ($documentIdA, $documentA, $documentB): \Shopware\Core\Checkout\Document\Renderer\RenderedDocument {
                static::assertNull($fileType);

                return $id === $documentIdA ? $documentA : $documentB;
            });

        $resolver = new AttachmentResolver($generator);
        $attachments = $resolver->resolveDocuments([$documentIdA, $documentIdB], $context);

        static::assertSame([
            [
                'content' => '%PDF-A',
                'fileName' => 'invoice_10001.pdf',
                'mimeType' => 'application/pdf',
            ],
            [
                'content' => '%PDF-B',
                'fileName' => 'delivery_note_10001.pdf',
                'mimeType' => 'application/pdf',
            ],
        ], $attachments);
    }

    public function testThrowsWhenDocumentCannotBeRead(): void
    {
        $documentId = Uuid::randomHex();
        $context = new Context(new SystemSource());

        $generator = $this->createMock(DocumentGenerator::class);
        $generator->method('readDocument')->willReturn(null);

        $resolver = new AttachmentResolver($generator);

        $this->expectException(MailCockpitException::class);
        $this->expectExceptionMessageMatches('/' . $documentId . '/');

        $resolver->resolveDocuments([$documentId], $context);
    }

    public function testEmptyDocumentListResolvesToEmptyArray(): void
    {
        $generator = $this->createMock(DocumentGenerator::class);
        $generator->expects(static::never())->method('readDocument');

        $resolver = new AttachmentResolver($generator);

        static::assertSame([], $resolver->resolveDocuments([], new Context(new SystemSource())));
    }
}
