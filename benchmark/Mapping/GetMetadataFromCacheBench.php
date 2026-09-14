<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Mapping;

use Doctrine\ODM\MongoDB\Benchmark\Fixtures\Car;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\RichDocument;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\SimpleDocument;
use Doctrine\ODM\MongoDB\DocumentManager;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Measures metadata lookups once the metadata cache is warm, isolating
 * cache-hit overhead from the driver-parsing cost measured by
 * LoadClassMetadataBench and WarmMetadataCacheBench.
 */
#[BeforeMethods(['init'])]
#[Warmup(2)]
#[Revs(1000)]
#[Iterations(2)]
final class GetMetadataFromCacheBench
{
    use MappingBenchTrait;

    private static DocumentManager $documentManager;

    public function init(): void
    {
        self::$documentManager = $this->createDocumentManager(withMetadataCache: true);

        // Prime the cache once, up front, so every benchmarked call is a hit.
        self::$documentManager->getMetadataFactory()->getAllMetadata();
    }

    public function benchGetSimpleDocument(): void
    {
        self::$documentManager->getClassMetadata(SimpleDocument::class);
    }

    public function benchGetDocumentWithInheritance(): void
    {
        self::$documentManager->getClassMetadata(Car::class);
    }

    public function benchGetDocumentWithRelations(): void
    {
        self::$documentManager->getClassMetadata(RichDocument::class);
    }

    public function benchGetAllMetadata(): void
    {
        self::$documentManager->getMetadataFactory()->getAllMetadata();
    }
}
