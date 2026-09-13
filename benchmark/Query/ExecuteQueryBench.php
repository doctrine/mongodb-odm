<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Query;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Documents\Group;
use Documents\User;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Measures Query\Builder execution, i.e. construction plus the driver
 * round trip and, where applicable, ODM hydration. Complements
 * BuildQueryBench (construction only) and HydrateDocumentBench (hydration
 * only, outside of the query pipeline).
 */
#[BeforeMethods(['initDocumentManager', 'clearDatabase', 'init'])]
#[Warmup(2)]
#[Revs(50)]
#[Iterations(5)]
final class ExecuteQueryBench extends BaseBench
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
            $user->addGroup($group1);
            $user->addGroup($group2);

            $this->getDocumentManager()->persist($user);
        }

        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchExecuteFindQuery(): void
    {
        $this->getDocumentManager()
            ->createQueryBuilder(User::class)
            ->field('username')->equals('user10')
            ->getQuery()
            ->getIterator()
            ->toArray();
    }

    public function benchExecuteFindQueryWithoutHydration(): void
    {
        $this->getDocumentManager()
            ->createQueryBuilder(User::class)
            ->hydrate(false)
            ->field('username')->equals('user10')
            ->getQuery()
            ->getIterator()
            ->toArray();
    }

    public function benchExecuteFindQueryWithReferencePriming(): void
    {
        $users = $this->getDocumentManager()
            ->createQueryBuilder(User::class)
            ->field('groups')->prime(true)
            ->getQuery()
            ->getIterator()
            ->toArray();

        foreach ($users as $user) {
            $user->getGroups()->count();
        }
    }
}
