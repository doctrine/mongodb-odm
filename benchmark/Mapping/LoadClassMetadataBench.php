<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Mapping;

use Doctrine\ODM\MongoDB\Benchmark\Fixtures\Car;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\RichDocument;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\SimpleDocument;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Measures the cost of loading class metadata from scratch (no metadata
 * cache), i.e. driver parsing plus ClassMetadataFactory construction. A
 * fresh DocumentManager is created for every revolution so that each call
 * genuinely hits the "cold" path instead of the factory's own runtime cache.
 */
#[Warmup(1)]
#[Revs(20)]
#[Iterations(2)]
final class LoadClassMetadataBench
{
    use MappingBenchTrait;

    public function benchLoadSimpleDocument(): void
    {
        $this->createDocumentManager()->getClassMetadata(SimpleDocument::class);
    }

    public function benchLoadDocumentWithInheritance(): void
    {
        $this->createDocumentManager()->getClassMetadata(Car::class);
    }

    public function benchLoadDocumentWithRelations(): void
    {
        $this->createDocumentManager()->getClassMetadata(RichDocument::class);
    }
}
