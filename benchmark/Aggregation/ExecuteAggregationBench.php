<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Aggregation;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Documents\Group;
use Documents\User;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Measures Aggregation\Builder execution, including the driver round trip
 * and, where applicable, ODM hydration of aggregation results.
 */
#[BeforeMethods(['initDocumentManager', 'clearDatabase', 'init'])]
#[Warmup(2)]
#[Revs(50)]
#[Iterations(5)]
final class ExecuteAggregationBench extends BaseBench
{
    private const NUMBER_OF_USERS = 25;

    public function init(): void
    {
        $group1 = new Group('One');
        $group2 = new Group('Two');

        $this->getDocumentManager()->persist($group1);
        $this->getDocumentManager()->persist($group2);

        for ($i = 0; $i < self::NUMBER_OF_USERS; $i++) {
            $user = new User();
            $user->setUsername('user' . $i);
            $user->setCreatedAt(new DateTimeImmutable());
            $user->setHits($i);
            $user->addGroup($i % 2 === 0 ? $group1 : $group2);
            $user->addGroupSimple($i % 2 === 0 ? $group1 : $group2);

            $this->getDocumentManager()->persist($user);
        }

        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchExecuteAggregationHydrated(): void
    {
        $builder = $this->getDocumentManager()->createAggregationBuilder(User::class);
        $builder->hydrate(User::class);

        $builder->match()
            ->field('hits')->gte(10);

        $builder->group()
            ->field('id')
            ->expression('$username')
            ->field('hits')
            ->sum('$hits');

        $builder->sort('hits', 'desc');

        $builder->getAggregation()->getIterator()->toArray();
    }

    public function benchExecuteAggregationRaw(): void
    {
        $builder = $this->getDocumentManager()->createAggregationBuilder(User::class);
        $builder->hydrate(null);

        $builder->match()
            ->field('hits')->gte(10);

        $builder->group()
            ->field('id')
            ->expression('$username')
            ->field('hits')
            ->sum('$hits');

        $builder->sort('hits', 'desc');

        $builder->getAggregation()->getIterator()->toArray();
    }

    public function benchExecuteAggregationWithLookup(): void
    {
        $builder = $this->getDocumentManager()->createAggregationBuilder(User::class);
        $builder->hydrate(null);

        $builder->lookup('groupsSimple')
            ->alias('groupDocuments');

        $builder->getAggregation()->getIterator()->toArray();
    }
}
