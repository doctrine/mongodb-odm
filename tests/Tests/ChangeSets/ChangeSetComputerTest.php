<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\ChangeSets;

use Closure;
use DateTime;
use Doctrine\ODM\MongoDB\ChangeSets\ChangeSet;
use Doctrine\ODM\MongoDB\ChangeSets\ChangeSetComputationRequest;
use Doctrine\ODM\MongoDB\ChangeSets\ChangeSetComputer;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Documents\Address;
use Documents\File;
use Documents\FileMetadata;
use Documents\Profile;
use Documents\ProfileNotify;
use Documents\User;
use PHPUnit\Framework\TestCase;
use stdClass;

use function spl_object_id;
use function sys_get_temp_dir;

/**
 * Deliberately does not extend BaseTestCase: that base class pings a real
 * MongoDB server during setUp() (transaction-support detection), which this
 * suite must not need — ChangeSetComputer is computed purely from class
 * metadata and plain arrays, no database required. A DocumentManager is
 * still built here, but only to resolve ClassMetadata via the attribute
 * driver; no command is ever sent to the (never-connected) client.
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
        if (self::$metadataOnlyDocumentManager !== null) {
            return self::$metadataOnlyDocumentManager;
        }

        $config = new Configuration();
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(sys_get_temp_dir());
        $config->setHydratorNamespace('Hydrators');
        $config->setPersistentCollectionDir(sys_get_temp_dir());
        $config->setPersistentCollectionNamespace('PersistentCollections');
        $config->setDefaultDB('doctrine_odm_changeset_computer_test');
        $config->setMetadataDriverImpl(AttributeDriver::create([__DIR__ . '/../../Documents']));

        // The client is never connected to: only getClassMetadata() is used below.
        return self::$metadataOnlyDocumentManager = DocumentManager::create(null, $config);
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
        self::assertSame(1, $results[spl_object_id($document1)]->getNewValue('hits'));
        self::assertSame(2, $results[spl_object_id($document2)]->getNewValue('hits'));
    }

    public function testSelectDocumentsForChangeSetComputationForDeferredImplicit(): void
    {
        $class = $this->getClassMetadata(User::class);

        $identityMap = ['user-1' => new User(), 'user-2' => new User()];

        $selected = $this->computer->selectDocumentsForChangeSetComputation($class, $identityMap, []);

        self::assertSame($identityMap, $selected);
    }

    public function testSelectDocumentsForChangeSetComputationForNotify(): void
    {
        $class = $this->getClassMetadata(ProfileNotify::class);

        $scheduled = [spl_object_id(new ProfileNotify()) => new ProfileNotify()];

        $selected = $this->computer->selectDocumentsForChangeSetComputation($class, ['profile-1' => new ProfileNotify()], $scheduled);

        self::assertSame($scheduled, $selected);
    }

    private function compute(ChangeSetComputationRequest $request, ?Closure $isCollectionScheduledForDeletion = null): ChangeSet
    {
        $results = $this->computer->computeChangeSets([$request], $isCollectionScheduledForDeletion ?? static fn () => false);

        return $results[spl_object_id($request->document)];
    }
}
