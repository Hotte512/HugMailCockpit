<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Service;

use Hug\MailCockpit\Exception\MailCockpitException;
use Hug\MailCockpit\Service\AttachmentResolver;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Document\DocumentCollection;
use Shopware\Core\Checkout\Document\Renderer\RenderedDocument;
use Shopware\Core\Checkout\Document\Service\DocumentGenerator;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;

class AttachmentResolverTest extends TestCase
{
    private string $orderId;

    private Context $context;

    protected function setUp(): void
    {
        $this->orderId = Uuid::randomHex();
        $this->context = new Context(new SystemSource());
    }

    /**
     * @param list<string> $ownedIds document ids the repository reports for the order
     *
     * @return EntityRepository<DocumentCollection>
     */
    private function documentRepository(array $ownedIds): EntityRepository
    {
        $data = [];
        foreach ($ownedIds as $id) {
            $data[$id] = ['primaryKey' => $id, 'data' => []];
        }

        $result = new IdSearchResult(\count($ownedIds), $data, new Criteria(), $this->context);

        /** @var EntityRepository<DocumentCollection> $repository */
        $repository = new StaticEntityRepository([$result]);

        return $repository;
    }

    public function testResolvesDocumentsToBinAttachmentArrays(): void
    {
        $documentIdA = Uuid::randomHex();
        $documentIdB = Uuid::randomHex();

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

        $resolver = new AttachmentResolver($generator, $this->documentRepository([$documentIdA, $documentIdB]));
        $attachments = $resolver->resolveDocuments([$documentIdA, $documentIdB], $this->orderId, $this->context);

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

    public function testRejectsDocumentThatDoesNotBelongToTheOrder(): void
    {
        $ownDocumentId = Uuid::randomHex();
        $foreignDocumentId = Uuid::randomHex();

        $generator = $this->createMock(DocumentGenerator::class);
        // The blob must never be read for a document the order does not own.
        $generator->expects(static::never())->method('readDocument');

        // Repository reports only the own document as belonging to the order.
        $resolver = new AttachmentResolver($generator, $this->documentRepository([$ownDocumentId]));

        $this->expectException(MailCockpitException::class);
        $this->expectExceptionMessageMatches('/' . $foreignDocumentId . '/');

        $resolver->resolveDocuments([$ownDocumentId, $foreignDocumentId], $this->orderId, $this->context);
    }

    public function testRejectsDocumentsWithoutOrderContext(): void
    {
        $generator = $this->createMock(DocumentGenerator::class);
        $generator->expects(static::never())->method('readDocument');

        $resolver = new AttachmentResolver($generator, $this->documentRepository([]));

        $this->expectException(MailCockpitException::class);
        $this->expectExceptionMessageMatches('/order context/');

        $resolver->resolveDocuments([Uuid::randomHex()], null, $this->context);
    }

    public function testThrowsWhenOwnedDocumentCannotBeRead(): void
    {
        $documentId = Uuid::randomHex();

        $generator = $this->createMock(DocumentGenerator::class);
        $generator->method('readDocument')->willReturn(null);

        $resolver = new AttachmentResolver($generator, $this->documentRepository([$documentId]));

        $this->expectException(MailCockpitException::class);
        $this->expectExceptionMessageMatches('/' . $documentId . '/');

        $resolver->resolveDocuments([$documentId], $this->orderId, $this->context);
    }

    public function testEmptyDocumentListResolvesToEmptyArray(): void
    {
        $generator = $this->createMock(DocumentGenerator::class);
        $generator->expects(static::never())->method('readDocument');

        $resolver = new AttachmentResolver($generator, $this->documentRepository([]));

        static::assertSame([], $resolver->resolveDocuments([], $this->orderId, $this->context));
    }
}
