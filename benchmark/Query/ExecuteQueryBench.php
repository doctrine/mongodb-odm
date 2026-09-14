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
 *
 * benchExecuteFindQuery and benchExecuteFindQueryWithReferencePriming
 * hydrate into managed documents, so (as in LoadDocumentBench) they're
 * pinned to a single, un-warmed-up revolution per iteration to avoid
 * UnitOfWork::getOrCreateDocument() silently skipping hydration on repeat
 * loads of the same documents. benchExecuteFindQueryWithoutHydration
 * returns raw arrays and never touches the identity map, so it can keep
 * more revolutions per iteration.
 */
#[BeforeMethods(['initDocumentManager', 'clearDatabase', 'init'])]
#[Warmup(2)]
#[Revs(20)]
#[Iterations(2)]
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

        // Prime the User (and, via the reference, Group) hydrator classes
        // once, untimed, so the timed revolutions below don't pay a
        // one-off class-generation cost that a warm application would
        // never see. The clear() first is essential: findBy() always
        // queries, but UnitOfWork::getOrCreateDocument() still skips
        // re-hydrating documents that are already managed and initialized
        // - which, without this clear(), all of them are (persist()+flush()
        // above left them that way).
        $this->getDocumentManager()->clear();

        foreach ($this->getDocumentManager()->getRepository(User::class)->findBy([]) as $primingUser) {
            $primingUser->getGroups()->count();
        }

        $this->getDocumentManager()->clear();
    }

    #[Warmup(0)]
    #[Revs(1)]
    #[Iterations(5)]
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

    #[Warmup(0)]
    #[Revs(1)]
    #[Iterations(5)]
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
