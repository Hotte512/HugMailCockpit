<?php

declare(strict_types=1);

namespace Hug\MailCockpit\Tests\Unit\Service;

use Hug\MailCockpit\Exception\MailCockpitException;
use Hug\MailCockpit\Service\MediaAttachmentGuard;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\Aggregate\MediaFolder\MediaFolderCollection;
use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\IdSearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Test\Stub\DataAbstractionLayer\StaticEntityRepository;

class MediaAttachmentGuardTest extends TestCase
{
    private Context $context;

    protected function setUp(): void
    {
        $this->context = new Context(new AdminApiSource(Uuid::randomHex()));
    }

    /**
     * @param list<string> $ids
     *
     * @return array<string, array{primaryKey: string, data: array<string, mixed>}>
     */
    private function idData(array $ids): array
    {
        $data = [];
        foreach ($ids as $id) {
            $data[$id] = ['primaryKey' => $id, 'data' => []];
        }

        return $data;
    }

    /**
     * @param list<string> $ids
     *
     * @return EntityRepository<MediaCollection>
     */
    private function mediaRepository(array $ids): EntityRepository
    {
        $result = new IdSearchResult(\count($ids), $this->idData($ids), new Criteria(), $this->context);

        /** @var EntityRepository<MediaCollection> $repository */
        $repository = new StaticEntityRepository([$result]);

        return $repository;
    }

    /**
     * @param list<string> $ids
     *
     * @return EntityRepository<MediaFolderCollection>
     */
    private function mediaFolderRepository(array $ids): EntityRepository
    {
        $result = new IdSearchResult(\count($ids), $this->idData($ids), new Criteria(), $this->context);

        /** @var EntityRepository<MediaFolderCollection> $repository */
        $repository = new StaticEntityRepository([$result]);

        return $repository;
    }

    public function testAllowsMediaFromTheAttachmentFolder(): void
    {
        $folderId = Uuid::randomHex();
        $mediaId = Uuid::randomHex();

        $guard = new MediaAttachmentGuard(
            $this->mediaRepository([$mediaId]),
            $this->mediaFolderRepository([$folderId]),
        );

        $guard->assertAllowed([$mediaId], $this->context);

        // No exception == allowed.
        $this->addToAssertionCount(1);
    }

    public function testRejectsMediaOutsideTheAttachmentFolder(): void
    {
        $folderId = Uuid::randomHex();
        $foreignMediaId = Uuid::randomHex();

        $guard = new MediaAttachmentGuard(
            // Media repository (filtered by folder) reports no match.
            $this->mediaRepository([]),
            $this->mediaFolderRepository([$folderId]),
        );

        $this->expectException(MailCockpitException::class);
        $this->expectExceptionMessageMatches('/' . $foreignMediaId . '/');

        $guard->assertAllowed([$foreignMediaId], $this->context);
    }

    public function testRejectsWhenAttachmentFolderIsMissing(): void
    {
        $guard = new MediaAttachmentGuard(
            $this->mediaRepository([Uuid::randomHex()]),
            // No plugin folder exists.
            $this->mediaFolderRepository([]),
        );

        $this->expectException(MailCockpitException::class);

        $guard->assertAllowed([Uuid::randomHex()], $this->context);
    }

    public function testEmptyMediaListIsANoop(): void
    {
        // Repositories that would throw if queried — proving the early return.
        $guard = new MediaAttachmentGuard(
            $this->mediaRepository([]),
            $this->mediaFolderRepository([]),
        );

        $guard->assertAllowed([], $this->context);

        $this->addToAssertionCount(1);
    }
}
