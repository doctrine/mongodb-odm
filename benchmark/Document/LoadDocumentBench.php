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

use function assert;

/**
 * Reads Fixtures\RichDocument rather than the test suite's
 * Documents\User: User has ten EmbedMany/ReferenceMany fields, each
 * unconditionally constructing a PersistentCollection during hydration
 * regardless of whether that field has data, which dominates its
 * hydration cost and would swamp the differences these benchmarks are
 * meant to isolate.
 *
 * Every subject here reads a document that was persisted in init(). Once a
 * managed document is already fully initialized, UnitOfWork::getOrCreateDocument()
 * skips hydration entirely unless a refresh is requested, so a second
 * revolution against the same document would measure "driver round trip
 * plus skipped hydration" rather than a real load. Revs is therefore kept
 * at 1: since each iteration runs in its own process (see
 * Runner::runIteration()), that's enough to guarantee a cold identity map
 * without needing clear() (which would itself pollute the timed block) or
 * a pool of documents to rotate through. Warmup defaults to 0 (no Warmup
 * attribute at any level in this class), which matters for the same
 * reason: a warmup call runs the same subject in the same process, which
 * would warm the identity map before the timed revolution ever runs.
 * Iterations is raised to make up for the lost statistical power of a
 * single rev.
 */
#[BeforeMethods(['initDocumentManager', 'clearDatabase', 'init'])]
#[Revs(1)]
#[Iterations(5)]
final class LoadDocumentBench extends BaseBench
{
    private const NUMBER_OF_ADDITIONAL_DOCUMENTS = 25;

    private static string $documentId;

    private static string $referenceManyDocumentId;

    protected static function createMetadataDriverImpl(): AttributeDriver
    {
        return AttributeDriver::create(__DIR__ . '/../Fixtures');
    }

    public function init(): void
    {
        $department       = new Department();
        $department->name = 'Engineering';

        $address          = new Address();
        $address->street  = 'Redacted';
        $address->city    = 'Munich';
        $address->zipCode = '80331';

        $team1       = new Team();
        $team1->name = 'One';

        $team2       = new Team();
        $team2->name = 'Two';

        $tag       = new Tag();
        $tag->name = 'benchmark';

        $document             = new RichDocument();
        $document->title      = 'benchmark';
        $document->address    = $address;
        $document->department = $department;
        $document->tags->add($tag);
        $document->teams->add($team1);
        $document->teams->add($team2);

        $this->getDocumentManager()->persist($department);
        $this->getDocumentManager()->persist($team1);
        $this->getDocumentManager()->persist($team2);
        $this->getDocumentManager()->persist($document);

        for ($i = 0; $i < self::NUMBER_OF_ADDITIONAL_DOCUMENTS; $i++) {
            $additionalDocument        = new RichDocument();
            $additionalDocument->title = 'doc' . $i;

            $this->getDocumentManager()->persist($additionalDocument);
        }

        $referenceManyDocument        = new RichDocument();
        $referenceManyDocument->title = 'teamDoc';
        $referenceManyDocument->teams->add($team1);
        $referenceManyDocument->teams->add($team2);

        $this->getDocumentManager()->persist($referenceManyDocument);

        $this->getDocumentManager()->flush();

        // Prime hydrator classes (one generated class per document type,
        // including embedded ones - see the RichDocument hydrator) and
        // lazy-reference proxy classes once, untimed, so the timed
        // revolutions below don't pay a one-off class-generation cost that
        // a warm application would never see. Embedded fields (address,
        // tags) are hydrated eagerly by find(), but references (department,
        // teams) are lazy and only hydrated on access. The clear() first is
        // essential: persist()+flush() leaves $document managed, so without
        // it find() would be satisfied by the identity map and never
        // actually hydrate anything.
        $this->getDocumentManager()->clear();
        $primingDocument = $this->getDocumentManager()->find(RichDocument::class, $document->id);
        assert($primingDocument instanceof RichDocument);
        $primingDepartmentName = $primingDocument->department?->name;
        foreach ($primingDocument->teams as $team) {
            assert($team instanceof Team);
        }

        self::$documentId              = $document->id;
        self::$referenceManyDocumentId = $referenceManyDocument->id;

        $this->getDocumentManager()->clear();
    }

    public function benchLoadDocument(): void
    {
        $this->loadDocument();
    }

    public function benchLoadEmbedOne(): void
    {
        $this->loadDocument()->address?->city;
    }

    public function benchLoadEmbedMany(): void
    {
        foreach ($this->loadDocument()->tags as $tag) {
            assert($tag instanceof Tag);
        }
    }

    public function benchLoadReferenceOne(): void
    {
        $this->loadDocument()->department?->name;
    }

    public function benchLoadReferenceMany(): void
    {
        foreach ($this->loadDocument()->teams as $team) {
            assert($team instanceof Team);
        }
    }

    public function benchLoadDocumentFromIdentityMap(): void
    {
        // Cold load, then load again without an intervening clear() so the
        // second call hits UnitOfWork::tryGetById(). Both calls belong in
        // the same revolution on purpose: it's the cold/warm pair itself
        // that's being measured.
        $this->loadDocument();
        $this->loadDocument();
    }

    public function benchLoadDocumentByQuery(): void
    {
        $this->getDocumentManager()->getRepository(RichDocument::class)->findOneBy(['title' => 'benchmark']);
    }

    public function benchLoadCollectionOfDocuments(): void
    {
        $this->getDocumentManager()->getRepository(RichDocument::class)->findBy([]);
    }

    public function benchLoadReferenceManyCollectionInitialization(): void
    {
        $document = $this->getDocumentManager()->find(RichDocument::class, self::$referenceManyDocumentId);
        assert($document instanceof RichDocument);

        $document->teams->count();
    }

    private function loadDocument(): RichDocument
    {
        $document = $this->getDocumentManager()->find(RichDocument::class, self::$documentId);
        assert($document instanceof RichDocument);

        return $document;
    }
}
