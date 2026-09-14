<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Query;

use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Documents\User;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Measures Query\Builder/Expr construction cost in isolation, without
 * executing anything against MongoDB. This isolates the fluent-API and
 * field-name-resolution overhead from driver round-trip time, which
 * ExecuteFindQueryBench measures separately.
 */
#[BeforeMethods(['initDocumentManager'])]
#[Warmup(2)]
#[Revs(1000)]
#[Iterations(2)]
final class BuildQueryBench extends BaseBench
{
    public function benchBuildSimpleQuery(): void
    {
        $this->getDocumentManager()
            ->createQueryBuilder(User::class)
            ->field('username')->equals('alcaeus')
            ->getQueryArray();
    }

    public function benchBuildComplexQuery(): void
    {
        $qb = $this->getDocumentManager()->createQueryBuilder(User::class);

        $qb->addOr($qb->expr()->field('username')->equals('alcaeus'));
        $qb->addOr(
            $qb->expr()
                ->field('hits')->gte(10)
                ->field('hits')->lt(1000),
        );
        $qb->addAnd($qb->expr()->field('address.city')->equals('Munich'));
        $qb->field('deletedAt')->exists(false);
        $qb->field('groups')->in(['groupOne', 'groupTwo', 'groupThree']);
        $qb->sort('createdAt', 'desc');
        $qb->limit(20);

        $qb->getQueryArray();
    }
}
