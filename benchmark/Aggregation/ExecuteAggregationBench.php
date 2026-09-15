<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Aggregation;

use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\RichDocument;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\Team;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

/**
 * Measures Aggregation\Builder execution, including the driver round trip
 * and, where applicable, ODM hydration of aggregation results. Aggregates
 * Fixtures\RichDocument rather than the test suite's Documents\User: User
 * has ten EmbedMany/ReferenceMany fields, each unconditionally
 * constructing a PersistentCollection during hydration regardless of
 * whether that field has data, which dominates its hydration cost and
 * would swamp the differences these benchmarks are meant to isolate.
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
            $document->teams->add($i % 2 === 0 ? $team1 : $team2);

            $this->getDocumentManager()->persist($document);
        }

        $this->getDocumentManager()->flush();

        // Prime the RichDocument hydrator class, plus Aggregation\Builder
        // construction itself (its own generated-code paths aren't touched
        // until a pipeline is first built), once, untimed, so the timed
        // revolutions below don't pay a one-off class-generation cost that
        // a warm application would never see. The clear() first is
        // essential: findOneBy() always queries, but
        // UnitOfWork::getOrCreateDocument() still skips re-hydrating a
        // document that's already managed and initialized - which, without
        // this clear(), it is (persist()+flush() above left it that way).
        $this->getDocumentManager()->clear();
        $this->getDocumentManager()->getRepository(RichDocument::class)->findOneBy([]);

        $warmupBuilder = $this->getDocumentManager()->createAggregationBuilder(RichDocument::class);
        $warmupBuilder->hydrate(RichDocument::class);
        $warmupBuilder->match()->field('score')->gte(0);
        $warmupBuilder->group()->field('id')->expression('$title')->field('score')->sum('$score');
        $warmupBuilder->sort('score', 'desc');

        $this->getDocumentManager()->clear();
    }

    #[Warmup(0)]
    #[Revs(1)]
    #[Iterations(5)]
    public function benchExecuteAggregationHydrated(): void
    {
        $builder = $this->getDocumentManager()->createAggregationBuilder(RichDocument::class);
        $builder->hydrate(RichDocument::class);

        $builder->match()
            ->field('score')->gte(10);

        $builder->group()
            ->field('id')
            ->expression('$title')
            ->field('score')
            ->sum('$score');

        $builder->sort('score', 'desc');

        $builder->getAggregation()->getIterator()->toArray();
    }

    public function benchExecuteAggregationRaw(): void
    {
        $builder = $this->getDocumentManager()->createAggregationBuilder(RichDocument::class);
        $builder->hydrate(null);

        $builder->match()
            ->field('score')->gte(10);

        $builder->group()
            ->field('id')
            ->expression('$title')
            ->field('score')
            ->sum('$score');

        $builder->sort('score', 'desc');

        $builder->getAggregation()->getIterator()->toArray();
    }

    public function benchExecuteAggregationWithLookup(): void
    {
        $builder = $this->getDocumentManager()->createAggregationBuilder(RichDocument::class);
        $builder->hydrate(null);

        $builder->lookup('teams')
            ->alias('teamDocuments');

        $builder->getAggregation()->getIterator()->toArray();
    }
}
