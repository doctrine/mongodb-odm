<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Query;

use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\RichDocument;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\Team;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Measures Query\Builder execution, i.e. construction plus the driver
 * round trip and, where applicable, ODM hydration. Complements
 * BuildQueryBench (construction only) and HydrateDocumentBench (hydration
 * only, outside of the query pipeline). Queries Fixtures\RichDocument
 * rather than the test suite's Documents\User: User has ten
 * EmbedMany/ReferenceMany fields, each unconditionally constructing a
 * PersistentCollection during hydration regardless of whether that field
 * has data, which dominates its hydration cost and would swamp the
 * differences these benchmarks are meant to isolate.
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
    private const NUMBER_OF_DOCUMENTS = 25;

    protected static function createMetadataDriverImpl(): AttributeDriver
    {
        return AttributeDriver::create(__DIR__ . '/../Fixtures');
    }

    public function init(): void
    {
        $team1       = new Team();
        $team1->name = 'One';

        $team2       = new Team();
        $team2->name = 'Two';

        $this->getDocumentManager()->persist($team1);
        $this->getDocumentManager()->persist($team2);

        for ($i = 0; $i < self::NUMBER_OF_DOCUMENTS; $i++) {
            $document        = new RichDocument();
            $document->title = 'doc' . $i;
            $document->score = $i;
            $document->teams->add($team1);
            $document->teams->add($team2);

            $this->getDocumentManager()->persist($document);
        }

        $this->getDocumentManager()->flush();

        // Prime the RichDocument (and, via the reference, Team) hydrator
        // classes, plus Query\Builder/Expr construction itself (its own
        // generated-code paths aren't touched until first built), once,
        // untimed, so the timed revolutions below don't pay a one-off
        // class-generation cost that a warm application would never see.
        // The clear() first is essential: findBy() always queries, but
        // UnitOfWork::getOrCreateDocument() still skips re-hydrating
        // documents that are already managed and initialized - which,
        // without this clear(), all of them are (persist()+flush() above
        // left them that way).
        $this->getDocumentManager()->clear();

        foreach ($this->getDocumentManager()->getRepository(RichDocument::class)->findBy([]) as $primingDocument) {
            $primingDocument->teams->count();
        }

        $this->getDocumentManager()->createQueryBuilder(RichDocument::class)->field('title')->equals('warmup')->getQuery();

        $this->getDocumentManager()->clear();
    }

    #[Warmup(0)]
    #[Revs(1)]
    #[Iterations(5)]
    public function benchExecuteFindQuery(): void
    {
        $this->getDocumentManager()
            ->createQueryBuilder(RichDocument::class)
            ->field('title')->equals('doc10')
            ->getQuery()
            ->getIterator()
            ->toArray();
    }

    public function benchExecuteFindQueryWithoutHydration(): void
    {
        $this->getDocumentManager()
            ->createQueryBuilder(RichDocument::class)
            ->hydrate(false)
            ->field('title')->equals('doc10')
            ->getQuery()
            ->getIterator()
            ->toArray();
    }

    #[Warmup(0)]
    #[Revs(1)]
    #[Iterations(5)]
    public function benchExecuteFindQueryWithReferencePriming(): void
    {
        $documents = $this->getDocumentManager()
            ->createQueryBuilder(RichDocument::class)
            ->field('teams')->prime(true)
            ->getQuery()
            ->getIterator()
            ->toArray();

        foreach ($documents as $document) {
            $document->teams->count();
        }
    }
}
