<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Service;

use Hug\MailCockpit\Core\Content\MailReference\MailReferenceCollection;
use Hug\MailCockpit\Core\Content\MailReference\MailReferenceDefinition;
use Hug\MailCockpit\Exception\MailCockpitException;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * Post-send audit trail (konzept.md §1): who sent what from which entry point.
 * Mail contents live in FroshPlatformMailArchive, never here.
 */
class MailReferenceWriter
{
    private const VALID_SOURCES = [
        MailReferenceDefinition::SOURCE_FREE,
        MailReferenceDefinition::SOURCE_DOCUMENT,
        MailReferenceDefinition::SOURCE_PREVIEW,
    ];

    /**
     * @param EntityRepository<MailReferenceCollection> $mailReferenceRepository
     */
    public function __construct(
        private readonly EntityRepository $mailReferenceRepository,
    ) {
    }

    /**
     * @param list<string> $documentIds one reference row is written per document
     */
    public function write(string $source, ?string $orderId, array $documentIds, Context $context): void
    {
        if (!\in_array($source, self::VALID_SOURCES, true)) {
            throw MailCockpitException::invalidSource($source);
        }

        $contextSource = $context->getSource();
        $base = [
            'source' => $source,
            'sentByUserId' => $contextSource instanceof AdminApiSource ? $contextSource->getUserId() : null,
        ];

        if ($orderId !== null) {
            $base['orderId'] = $orderId;
        }

        if ($documentIds === []) {
            $rows = [['id' => Uuid::randomHex(), ...$base]];
        } else {
            $rows = array_map(
                static fn (string $documentId): array => [
                    'id' => Uuid::randomHex(),
                    'documentId' => $documentId,
                    ...$base,
                ],
                $documentIds,
            );
        }

        $this->mailReferenceRepository->create(array_values($rows), $context);
    }
}
