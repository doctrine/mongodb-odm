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
 *
 * benchExecuteAggregationHydrated hydrates into managed documents, so (as
 * in LoadDocumentBench) it's pinned to a single, un-warmed-up revolution
 * per iteration to avoid UnitOfWork::getOrCreateDocument() silently
 * skipping hydration on repeat runs of the same grouping query. The raw
 * variants never touch the identity map, so they can keep more
 * revolutions per iteration.
 */
#[BeforeMethods(['initDocumentManager', 'clearDatabase', 'init'])]
#[Warmup(2)]
#[Revs(20)]
#[Iterations(2)]
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

        // Prime the User hydrator class once, untimed, so the timed
        // revolutions below don't pay a one-off class-generation cost that
        // a warm application would never see. The clear() first is
        // essential: findOneBy() always queries, but
        // UnitOfWork::getOrCreateDocument() still skips re-hydrating a
        // document that's already managed and initialized - which, without
        // this clear(), it is (persist()+flush() above left it that way).
        $this->getDocumentManager()->clear();
        $this->getDocumentManager()->getRepository(User::class)->findOneBy([]);

        $this->getDocumentManager()->clear();
    }

    #[Warmup(0)]
    #[Revs(1)]
    #[Iterations(5)]
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
