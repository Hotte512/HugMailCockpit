<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

use Hug\MailCockpit\Exception\MailCockpitException;
use Shopware\Core\Checkout\Document\DocumentCollection;
use Shopware\Core\Checkout\Document\Service\DocumentGenerator;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class AttachmentResolver
{
    /**
     * @param EntityRepository<DocumentCollection> $documentRepository
     */
    public function __construct(
        private readonly DocumentGenerator $documentGenerator,
        private readonly EntityRepository $documentRepository,
    ) {
    }

    /**
     * Resolves generated documents to the binAttachments structure expected by
     * MailFactory (same mapping as the core MailAttachmentsBuilder). In 6.7
     * readDocument() returns the file blob as string — no stream handling.
     * Missing documents throw: an attachment the user selected must never be
     * dropped silently.
     *
     * Security: readDocument() loads the blob under the system scope with an
     * empty deepLinkCode, so it enforces no ownership itself. We therefore bind
     * every requested document to $orderId first — otherwise any authenticated
     * sender could attach a foreign order's invoice and exfiltrate it.
     *
     * @param list<string> $documentIds
     *
     * @return list<array{content: string, fileName: string, mimeType: string}>
     */
    public function resolveDocuments(array $documentIds, ?string $orderId, Context $context): array
    {
        if ($documentIds === []) {
            return [];
        }

        if ($orderId === null) {
            throw MailCockpitException::documentsRequireOrder();
        }

        $this->assertDocumentsBelongToOrder($documentIds, $orderId, $context);

        $attachments = [];

        foreach ($documentIds as $documentId) {
            // fileType: null keeps the stored file extension of the document
            $document = $this->documentGenerator->readDocument($documentId, $context, '', null);

            if ($document === null) {
                throw MailCockpitException::documentNotFound($documentId);
            }

            $attachments[] = [
                'content' => $document->getContent(),
                'fileName' => $document->getName(),
                'mimeType' => $document->getContentType(),
            ];
        }

        return $attachments;
    }

    /**
     * @param list<string> $documentIds
     */
    private function assertDocumentsBelongToOrder(array $documentIds, string $orderId, Context $context): void
    {
        $criteria = new Criteria($documentIds);
        $criteria->addFilter(new EqualsFilter('orderId', $orderId));

        $ownedIds = $this->documentRepository->searchIds($criteria, $context)->getIds();

        foreach ($documentIds as $documentId) {
            if (!\in_array($documentId, $ownedIds, true)) {
                // 404 (not "forbidden") so a foreign but existing id is
                // indistinguishable from a missing one — no existence oracle.
                throw MailCockpitException::documentNotFound($documentId);
            }
        }
    }
}
