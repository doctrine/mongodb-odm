<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Mapping;

use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Measures the cost of warming up the metadata factory for the full set
 * of benchmark fixtures at once, i.e. what a cold application boot pays
 * to load metadata for every mapped class up front.
 */
#[Warmup(1)]
#[Revs(20)]
#[Iterations(5)]
final class WarmMetadataCacheBench
{
    use MappingBenchTrait;

    public function benchLoadAllMetadata(): void
    {
        $this->createDocumentManager()->getMetadataFactory()->getAllMetadata();
    }
}
