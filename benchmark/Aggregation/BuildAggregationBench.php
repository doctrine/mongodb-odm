<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Aggregation;

use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Documents\User;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Measures Aggregation\Builder/Expr construction cost in isolation,
 * without executing anything against MongoDB.
 */
#[BeforeMethods(['initDocumentManager'])]
#[Warmup(2)]
#[Revs(1000)]
#[Iterations(2)]
final class BuildAggregationBench extends BaseBench
{
    public function benchBuildAggregationPipeline(): void
    {
        $builder = $this->getDocumentManager()->createAggregationBuilder(User::class);

        $builder->match()
            ->field('hits')->gte(10);

        $builder->group()
            ->field('id')
            ->expression('$address.city')
            ->field('numUsers')
            ->sum(1);

        $builder->project()
            ->includeFields(['numUsers']);

        $builder->sort('numUsers', 'desc');

        $builder->getPipeline();
    }
}
