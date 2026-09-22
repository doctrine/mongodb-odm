<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\ChangeSets;

use Closure;
use DateTime;
use Doctrine\ODM\MongoDB\ChangeSets\ChangeSet;
use Doctrine\ODM\MongoDB\ChangeSets\ChangeSetComputationRequest;
use Doctrine\ODM\MongoDB\ChangeSets\ChangeSetComputer;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Address;
use Documents\File;
use Documents\FileMetadata;
use Documents\Profile;
use Documents\User;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Deliberately does not extend BaseTestCase: that base class pings a real
 * MongoDB server during setUp() (transaction-support detection), which this
 * suite must not need — ChangeSetComputer is computed purely from class
 * metadata and plain arrays, no database required. It does reuse
 * {@see BaseTestCase::createMetadataOnlyDocumentManager()} to resolve
 * ClassMetadata via the attribute driver, without connecting to a client.
 */
class ChangeSetComputerTest extends TestCase
{
    private static ?DocumentManager $metadataOnlyDocumentManager = null;

    private ChangeSetComputer $computer;

    protected function setUp(): void
    {
        $this->computer = new ChangeSetComputer();
    }

    /**
     * @param class-string<T> $className
     *
     * @return ClassMetadata<T>
     *
     * @template T of object
     */
    private function getClassMetadata(string $className): ClassMetadata
    {
        return self::getMetadataOnlyDocumentManager()->getClassMetadata($className);
    }

    private static function getMetadataOnlyDocumentManager(): DocumentManager
    {
        return self::$metadataOnlyDocumentManager ??= BaseTestCase::createMetadataOnlyDocumentManager();
    }

    public function testNewDocumentRecordsEveryFieldAsChanged(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();
        $profile  = new Profile();

        $changeSet = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            null,
            ['hits' => 3, 'profile' => $profile, 'simpleReferenceOneInverse' => new User()],
            null,
            false,
            false,
        ));

        self::assertTrue($changeSet->hasChangedField('hits'));
        self::assertNull($changeSet->getOldValue('hits'));
        self::assertSame(3, $changeSet->getNewValue('hits'));

        self::assertTrue($changeSet->hasChangedField('profile'));
        self::assertSame($profile, $changeSet->getNewValue('profile'));

        // inverse side of a reference relationship is never recorded
        self::assertFalse($changeSet->hasChangedField('simpleReferenceOneInverse'));
    }

    public function testManagedDocumentSkipsNotSavedField(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();

        $changeSet = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['sortedAscGroups' => 'old'],
            ['sortedAscGroups' => 'new'],
            null,
            false,
            false,
        ));

        self::assertFalse($changeSet->hasChangedField('sortedAscGroups'));
    }

    public function testGridFsOnlyDiffsMetadataField(): void
    {
        $class    = $this->getClassMetadata(File::class);
        $document = new File();
        $oldMeta  = new FileMetadata();
        $newMeta  = new FileMetadata();

        $changeSet = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['filename' => 'old.txt', 'metadata' => $oldMeta],
            ['filename' => 'new.txt', 'metadata' => $newMeta],
            null,
            false,
            false,
        ));

        self::assertFalse($changeSet->hasChangedField('filename'));
        self::assertTrue($changeSet->hasChangedField('metadata'));
        self::assertSame($newMeta, $changeSet->getNewValue('metadata'));
    }

    public function testEmbedOneReplacementIsRecorded(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();
        $oldValue = new Address();
        $newValue = new Address();

        $changeSet = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['address' => $oldValue],
            ['address' => $newValue],
            null,
            false,
            false,
        ));

        self::assertTrue($changeSet->hasChangedField('address'));
        self::assertSame($oldValue, $changeSet->getOldValue('address'));
        self::assertSame($newValue, $changeSet->getNewValue('address'));
    }

    public function testReferenceOneOwningSideReplacementIsRecorded(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();
        $oldValue = new Profile();
        $newValue = new Profile();

        $changeSet = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['profile' => $oldValue],
            ['profile' => $newValue],
            null,
            false,
            false,
        ));

        self::assertTrue($changeSet->hasChangedField('profile'));
        self::assertSame($newValue, $changeSet->getNewValue('profile'));
    }

    public function testReferenceOneInverseSideIsNotRecordedForManagedDocument(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();

        $changeSet = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['simpleReferenceOneInverse' => new User()],
            ['simpleReferenceOneInverse' => new User()],
            null,
            false,
            false,
        ));

        self::assertFalse($changeSet->hasChangedField('simpleReferenceOneInverse'));
    }

    public function testChangeTrackingNotifySkipsRegularFieldButStillRecordsAssociations(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();
        $address  = new Address();

        $changeSet = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['hits' => 1, 'address' => null],
            ['hits' => 2, 'address' => $address],
            null,
            true,
            false,
        ));

        self::assertFalse($changeSet->hasChangedField('hits'));
        self::assertTrue($changeSet->hasChangedField('address'));
    }

    public function testNotifyReusesExistingChangeSetUnlessForceRecompute(): void
    {
        $class        = $this->getClassMetadata(User::class);
        $document     = new User();
        $originalData = ['hits' => 1, 'address' => null];
        $existing     = new ChangeSet($document, $originalData);
        $existing->recordChange('hits', 42); // e.g. pushed in by propertyChanged()

        $address = new Address();

        $reused = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            $originalData,
            ['hits' => 1, 'address' => $address],
            $existing,
            true,
            false,
        ));

        self::assertSame($existing, $reused);
        self::assertTrue($reused->hasChangedField('hits'));
        self::assertSame(42, $reused->getNewValue('hits'));
        self::assertTrue($reused->hasChangedField('address'));

        $recomputed = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            $originalData,
            ['hits' => 1, 'address' => $address],
            $existing,
            true,
            true,
        ));

        self::assertNotSame($existing, $recomputed);
        self::assertFalse($recomputed->hasChangedField('hits'));
        self::assertTrue($recomputed->hasChangedField('address'));
    }

    public function testReferenceManyChangeIsRecordedWhenActualDiffers(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();

        $changeSet = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['groups' => ['old']],
            ['groups' => ['new']],
            null,
            false,
            false,
        ));

        self::assertTrue($changeSet->hasChangedField('groups'));
    }

    public function testDirtyPersistentCollectionWithUnchangedReferenceIsRecorded(): void
    {
        $class      = $this->getClassMetadata(User::class);
        $document   = new User();
        $collection = $this->createMock(PersistentCollectionInterface::class);
        $collection->method('isDirty')->willReturn(true);

        $changeSet = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['groups' => $collection],
            ['groups' => $collection],
            null,
            false,
            false,
        ), static fn () => false);

        self::assertTrue($changeSet->hasChangedField('groups'));
    }

    public function testCleanPersistentCollectionScheduledForDeletionIsStillRecorded(): void
    {
        $class      = $this->getClassMetadata(User::class);
        $document   = new User();
        $collection = $this->createMock(PersistentCollectionInterface::class);
        $collection->method('isDirty')->willReturn(false);

        $changeSet = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['groups' => $collection],
            ['groups' => $collection],
            null,
            false,
            false,
        ), static fn () => true);

        self::assertTrue($changeSet->hasChangedField('groups'));
    }

    public function testCleanUnscheduledPersistentCollectionIsNotRecorded(): void
    {
        $class      = $this->getClassMetadata(User::class);
        $document   = new User();
        $collection = $this->createMock(PersistentCollectionInterface::class);
        $collection->method('isDirty')->willReturn(false);

        $changeSet = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['groups' => $collection],
            ['groups' => $collection],
            null,
            false,
            false,
        ), static fn () => false);

        self::assertFalse($changeSet->hasChangedField('groups'));
    }

    public function testEquivalentDateValuesAreSkipped(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();

        $changeSet = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['disabledAt' => new DateTime('2024-01-01 12:00:00')],
            ['disabledAt' => new DateTime('2024-01-01 12:00:00')],
            null,
            false,
            false,
        ));

        self::assertFalse($changeSet->hasChangedField('disabledAt'));
    }

    public function testDifferingDateValuesAreRecorded(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();

        $changeSet = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['disabledAt' => new DateTime('2024-01-01 12:00:00')],
            ['disabledAt' => new DateTime('2024-01-02 12:00:00')],
            null,
            false,
            false,
        ));

        self::assertTrue($changeSet->hasChangedField('disabledAt'));
    }

    public function testReadOnlyClassReturnsExistingChangeSetUnchanged(): void
    {
        $class             = new ClassMetadata(stdClass::class);
        $class->isReadOnly = true;
        $document          = new stdClass();
        $existing          = new ChangeSet($document, ['name' => 'Alice']);

        $result = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['name' => 'Alice'],
            ['name' => 'Bob'],
            $existing,
            false,
            false,
        ));

        self::assertSame($existing, $result);
    }

    public function testReadOnlyClassWithoutExistingChangeSetReturnsEmptyChangeSet(): void
    {
        $class             = new ClassMetadata(stdClass::class);
        $class->isReadOnly = true;
        $document          = new stdClass();

        $result = $this->compute(new ChangeSetComputationRequest(
            $class,
            $document,
            ['name' => 'Alice'],
            ['name' => 'Bob'],
            null,
            false,
            false,
        ));

        self::assertTrue($result->isEmpty());
    }

    public function testComputeChangeSetsBatchesMultipleDocuments(): void
    {
        $class     = $this->getClassMetadata(User::class);
        $document1 = new User();
        $document2 = new User();

        $results = $this->computer->computeChangeSets(
            [
                new ChangeSetComputationRequest($class, $document1, null, ['hits' => 1], null, false, false),
                new ChangeSetComputationRequest($class, $document2, null, ['hits' => 2], null, false, false),
            ],
            static fn () => false,
        );

        self::assertCount(2, $results);
        self::assertSame(1, $results[$document1]->getNewValue('hits'));
        self::assertSame(2, $results[$document2]->getNewValue('hits'));
    }

    public function testComputeChangeSetForNewDocumentShortCircuits(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();

        $result = $this->computer->computeChangeSet(
            new ChangeSetComputationRequest($class, $document, null, ['hits' => 1], null, false, false),
            static fn () => false,
        );

        self::assertTrue($result->isNewDocument);
        self::assertSame([], $result->orphansToRemove);
        self::assertSame([], $result->collectionsToDelete);
    }

    public function testComputeChangeSetForUnchangedDocumentShortCircuits(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();

        $result = $this->computer->computeChangeSet(
            new ChangeSetComputationRequest($class, $document, ['hits' => 1], ['hits' => 1], null, false, false),
            static fn () => false,
        );

        self::assertFalse($result->isNewDocument);
        self::assertTrue($result->changeSet->isEmpty());
        self::assertSame([], $result->orphansToRemove);
        self::assertSame([], $result->collectionsToDelete);
    }

    public function testComputeChangeSetSchedulesEmbedOneOrphanRemoval(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();
        $oldValue = new Address();

        $result = $this->computer->computeChangeSet(
            new ChangeSetComputationRequest($class, $document, ['address' => $oldValue], ['address' => new Address()], null, false, false),
            static fn () => false,
        );

        self::assertSame([$oldValue], $result->orphansToRemove);
        self::assertSame([], $result->collectionsToDelete);
    }

    public function testComputeChangeSetSchedulesOwningReferenceOneOrphanRemovalOnlyWhenConfigured(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();
        $oldValue = new Profile();

        // User::$profile is mapped without orphanRemoval, so the old Profile is not orphaned.
        $result = $this->computer->computeChangeSet(
            new ChangeSetComputationRequest($class, $document, ['profile' => $oldValue], ['profile' => new Profile()], null, false, false),
            static fn () => false,
        );

        self::assertSame([], $result->orphansToRemove);
    }

    public function testComputeChangeSetSchedulesCollectionDeletionForReplacedCollection(): void
    {
        $class    = $this->getClassMetadata(User::class);
        $document = new User();
        $oldValue = $this->createMock(PersistentCollectionInterface::class);
        $oldValue->method('isDirty')->willReturn(false);
        $newValue = $this->createMock(PersistentCollectionInterface::class);
        $newValue->method('isDirty')->willReturn(false);

        $result = $this->computer->computeChangeSet(
            new ChangeSetComputationRequest($class, $document, ['groups' => $oldValue], ['groups' => $newValue], null, false, false),
            static fn () => false,
        );

        self::assertSame([$oldValue], $result->collectionsToDelete);
    }

    public function testComputeChangeSetSkipsCollectionDeletionForSameInstance(): void
    {
        $class      = $this->getClassMetadata(User::class);
        $document   = new User();
        $collection = $this->createMock(PersistentCollectionInterface::class);
        $collection->method('isDirty')->willReturn(true);

        $result = $this->computer->computeChangeSet(
            new ChangeSetComputationRequest($class, $document, ['groups' => $collection], ['groups' => $collection], null, false, false),
            static fn () => false,
        );

        self::assertSame([], $result->collectionsToDelete);
    }

    public function testComputeChangeSetMergesOntoExistingChangeSetUsingOnlyThisPassDiffForScheduling(): void
    {
        $class        = $this->getClassMetadata(User::class);
        $document     = new User();
        $oldAddress   = new Address();
        $originalData = ['hits' => 1, 'address' => $oldAddress];
        $existing     = new ChangeSet($document, $originalData);
        $existing->recordChange('hits', 42); // e.g. pushed in by propertyChanged()

        $newAddress = new Address();

        $result = $this->computer->computeChangeSet(
            new ChangeSetComputationRequest($class, $document, $originalData, ['hits' => 1, 'address' => $newAddress], $existing, false, false),
            static fn () => false,
        );

        // Merged: the earlier pass's "hits" change survives alongside this pass's "address" change.
        self::assertSame($existing, $result->changeSet);
        self::assertTrue($result->changeSet->hasChangedField('hits'));
        self::assertSame(42, $result->changeSet->getNewValue('hits'));
        self::assertTrue($result->changeSet->hasChangedField('address'));

        // Scheduling is derived from this pass's own diff (address only) rather than the merged
        // changeset, so the old address is orphaned exactly once, not once per pass.
        self::assertSame([$oldAddress], $result->orphansToRemove);
    }

    private function compute(ChangeSetComputationRequest $request, ?Closure $isCollectionScheduledForDeletion = null): ChangeSet
    {
        $results = $this->computer->computeChangeSets([$request], $isCollectionScheduledForDeletion ?? static fn () => false);

        return $results[$request->document];
    }
}
