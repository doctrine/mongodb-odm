<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Document;

use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\Address;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\Department;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\RichDocument;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\Tag;
use Doctrine\ODM\MongoDB\Benchmark\Fixtures\Team;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use PhpBench\Attributes\BeforeMethods;
use PhpBench\Attributes\Iterations;
use PhpBench\Attributes\Revs;
use PhpBench\Attributes\Warmup;

use function random_int;

use const PHP_INT_MAX;

/**
 * Persists Fixtures\RichDocument rather than the test suite's
 * Documents\User: User has ten EmbedMany/ReferenceMany fields, each
 * unconditionally constructing a PersistentCollection during hydration
 * regardless of whether that field has data, which dominates its
 * hydration cost and would swamp the differences these benchmarks are
 * meant to isolate.
 *
 * Each of these inserts/updates/removes a document over the network, so
 * revolutions mainly buy statistical stability rather than overcoming
 * timer resolution (unlike, say, HydrateDocumentBench's in-memory calls).
 * A round trip already dwarfs measurement noise, so a class-wide default
 * of 20 keeps the suite quick without losing precision.
 * benchComputeChangeSetsOnly is the one subject here that never touches
 * the network, so it gets a much higher rev count to match.
 */
#[BeforeMethods(['initDocumentManager', 'clearDatabase'])]
#[Warmup(2)]
#[Revs(20)]
#[Iterations(2)]
final class StoreDocumentBench extends BaseBench
{
    private static RichDocument $updateDocument;

    protected static function createMetadataDriverImpl(): AttributeDriver
    {
        return AttributeDriver::create(__DIR__ . '/../Fixtures');
    }

    public function benchStoreDocument(): void
    {
        $document        = new RichDocument();
        $document->title = 'benchmark';

        $this->getDocumentManager()->persist($document);
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchStoreDocumentWithEmbedOne(): void
    {
        $address          = new Address();
        $address->street  = 'Redacted';
        $address->city    = 'Munich';
        $address->zipCode = '80331';

        $document          = new RichDocument();
        $document->title   = 'benchmark';
        $document->address = $address;

        $this->getDocumentManager()->persist($document);
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchStoreDocumentWithEmbedMany(): void
    {
        $tag1       = new Tag();
        $tag1->name = 'One';

        $tag2       = new Tag();
        $tag2->name = 'Two';

        $document        = new RichDocument();
        $document->title = 'benchmark';
        $document->tags->add($tag1);
        $document->tags->add($tag2);

        $this->getDocumentManager()->persist($document);
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchStoreDocumentWithReferenceOne(): void
    {
        $department       = new Department();
        $department->name = 'Engineering';

        $document             = new RichDocument();
        $document->title      = 'benchmark';
        $document->department = $department;

        $this->getDocumentManager()->persist($department);
        $this->getDocumentManager()->persist($document);
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchStoreDocumentWithReferenceMany(): void
    {
        $team1       = new Team();
        $team1->name = 'One';

        $team2       = new Team();
        $team2->name = 'Two';

        $document        = new RichDocument();
        $document->title = 'benchmark';
        $document->teams->add($team1);
        $document->teams->add($team2);

        $this->getDocumentManager()->persist($team1);
        $this->getDocumentManager()->persist($team2);
        $this->getDocumentManager()->persist($document);
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function initUpdateBench(): void
    {
        $tag       = new Tag();
        $tag->name = 'One';

        $document        = new RichDocument();
        $document->title = 'benchmark';
        $document->score = 0;
        $document->tags->add($tag);

        $this->getDocumentManager()->persist($document);
        $this->getDocumentManager()->flush();

        self::$updateDocument = $document;
    }

    #[BeforeMethods(['initDocumentManager', 'clearDatabase', 'initUpdateBench'])]
    public function benchUpdateDocument(): void
    {
        self::$updateDocument->score++;

        $this->getDocumentManager()->flush();
    }

    #[BeforeMethods(['initDocumentManager', 'clearDatabase', 'initUpdateBench'])]
    public function benchUpdateDocumentWithEmbedMany(): void
    {
        // A fresh value every call: an unconditionally repeated value would
        // only produce a real diff on the first revolution (see
        // benchLoadDocumentFromIdentityMap's docblock for the same class of
        // issue with revs > 1), leaving flush() a no-op for the rest.
        self::$updateDocument->tags->first()->name = 'Updated' . random_int(0, PHP_INT_MAX);

        $this->getDocumentManager()->flush();
    }

    #[BeforeMethods(['initDocumentManager', 'clearDatabase', 'initUpdateBench'])]
    #[Revs(200)]
    public function benchComputeChangeSetsOnly(): void
    {
        self::$updateDocument->score++;

        $this->getDocumentManager()->getUnitOfWork()->computeChangeSets();
    }

    #[BeforeMethods(['initDocumentManager', 'clearDatabase'])]
    #[Revs(5)]
    public function benchStoreManyDocuments(): void
    {
        for ($i = 0; $i < 100; $i++) {
            $document        = new RichDocument();
            $document->title = 'benchmark' . $i;

            $this->getDocumentManager()->persist($document);
        }

        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }

    public function benchRemoveDocument(): void
    {
        $document        = new RichDocument();
        $document->title = 'benchmark';

        $this->getDocumentManager()->persist($document);
        $this->getDocumentManager()->flush();

        $this->getDocumentManager()->remove($document);
        $this->getDocumentManager()->flush();
        $this->getDocumentManager()->clear();
    }
}
