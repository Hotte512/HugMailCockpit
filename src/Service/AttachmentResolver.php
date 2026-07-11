<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

use Hug\MailCockpit\Exception\MailCockpitException;
use Shopware\Core\Checkout\Document\Service\DocumentGenerator;
use Shopware\Core\Framework\Context;

class AttachmentResolver
{
    public function __construct(
        private readonly DocumentGenerator $documentGenerator,
    ) {
    }

    /**
     * Resolves generated documents to the binAttachments structure expected by
     * MailFactory (same mapping as the core MailAttachmentsBuilder). In 6.7
     * readDocument() returns the file blob as string — no stream handling.
     * Missing documents throw: an attachment the user selected must never be
     * dropped silently.
     *
     * @param list<string> $documentIds
     *
     * @return list<array{content: string, fileName: string, mimeType: string}>
     */
    public function resolveDocuments(array $documentIds, Context $context): array
    {
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
}
