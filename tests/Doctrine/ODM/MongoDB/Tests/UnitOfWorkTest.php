<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Closure;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\APM\CommandLogger;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Tests\Mocks\ExceptionThrowingListenerMock;
use Doctrine\ODM\MongoDB\Tests\Mocks\PreUpdateListenerMock;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\Persistence\NotifyPropertyChanged;
use Doctrine\Persistence\PropertyChangedListener;
use Documents\Address;
use Documents\File;
use Documents\FileWithoutMetadata;
use Documents\ForumAvatar;
use Documents\ForumUser;
use Documents\Functional\NotSaved;
use Documents\User;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection as MongoDBCollection;
use MongoDB\Driver\WriteConcern;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use ProxyManager\Proxy\GhostObjectInterface;
use ReflectionProperty;
use Throwable;

use function end;
use function spl_object_hash;
use function sprintf;

class UnitOfWorkTest extends BaseTestCase
{
    public function testIsDocumentScheduled(): void
    {
        $class = $this->dm->getClassMetadata(ForumUser::class);
        $user  = new ForumUser();
        self::assertFalse($this->uow->isDocumentScheduled($user));
        $this->uow->scheduleForInsert($class, $user);
        self::assertTrue($this->uow->isDocumentScheduled($user));
    }

    public function testScheduleForInsert(): void
    {
        $class = $this->dm->getClassMetadata(ForumUser::class);
        $user  = new ForumUser();
        self::assertFalse($this->uow->isScheduledForInsert($user));
        $this->uow->scheduleForInsert($class, $user);
        self::assertTrue($this->uow->isScheduledForInsert($user));
    }

    public function testScheduleForUpsert(): void
    {
        $class    = $this->dm->getClassMetadata(ForumUser::class);
        $user     = new ForumUser();
        $user->id = new ObjectId();
        self::assertFalse($this->uow->isScheduledForInsert($user));
        self::assertFalse($this->uow->isScheduledForUpsert($user));
        $this->uow->scheduleForUpsert($class, $user);
        self::assertFalse($this->uow->isScheduledForInsert($user));
        self::assertTrue($this->uow->isScheduledForUpsert($user));
    }

    public function testGetScheduledDocumentUpserts(): void
    {
        $class    = $this->dm->getClassMetadata(ForumUser::class);
        $user     = new ForumUser();
        $user->id = new ObjectId();
        self::assertEmpty($this->uow->getScheduledDocumentUpserts());
        $this->uow->scheduleForUpsert($class, $user);
        self::assertEquals([spl_object_hash($user) => $user], $this->uow->getScheduledDocumentUpserts());
    }

    public function testScheduleForEmbeddedUpsert(): void
    {
        $test     = new EmbeddedUpsertDocument();
        $test->id = (string) new ObjectId();
        self::assertFalse($this->uow->isScheduledForInsert($test));
        self::assertFalse($this->uow->isScheduledForUpsert($test));
        $this->uow->persist($test);
        self::assertTrue($this->uow->isScheduledForInsert($test));
        self::assertFalse($this->uow->isScheduledForUpsert($test));
    }

    public function testScheduleForUpsertWithNonObjectIdValues(): void
    {
        $doc     = new UowCustomIdDocument();
        $doc->id = 'string';
        $class   = $this->dm->getClassMetadata($doc::class);
        self::assertFalse($this->uow->isScheduledForInsert($doc));
        self::assertFalse($this->uow->isScheduledForUpsert($doc));
        $this->uow->scheduleForUpsert($class, $doc);
        self::assertFalse($this->uow->isScheduledForInsert($doc));
        self::assertTrue($this->uow->isScheduledForUpsert($doc));
    }

    public function testScheduleForInsertShouldNotUpsertDocumentsWithInconsistentIdValues(): void
    {
        $class    = $this->dm->getClassMetadata(ForumUser::class);
        $user     = new ForumUser();
        $user->id = 1;
        self::assertFalse($this->uow->isScheduledForInsert($user));
        self::assertFalse($this->uow->isScheduledForUpsert($user));
        $this->uow->scheduleForInsert($class, $user);
        self::assertTrue($this->uow->isScheduledForInsert($user));
        self::assertFalse($this->uow->isScheduledForUpsert($user));
    }

    public function testRegisterRemovedOnNewEntityIsIgnored(): void
    {
        $user           = new ForumUser();
        $user->username = 'romanb';
        self::assertFalse($this->uow->isScheduledForDelete($user));
        $this->uow->scheduleForDelete($user);
        self::assertFalse($this->uow->isScheduledForDelete($user));
    }

    public function testScheduleForDeleteShouldUnregisterScheduledUpserts(): void
    {
        $class    = $this->dm->getClassMetadata(ForumUser::class);
        $user     = new ForumUser();
        $user->id = new ObjectId();
        self::assertFalse($this->uow->isScheduledForInsert($user));
        self::assertFalse($this->uow->isScheduledForUpsert($user));
        self::assertFalse($this->uow->isScheduledForDelete($user));
        $this->uow->scheduleForUpsert($class, $user);
        self::assertFalse($this->uow->isScheduledForInsert($user));
        self::assertTrue($this->uow->isScheduledForUpsert($user));
        self::assertFalse($this->uow->isScheduledForDelete($user));
        $this->uow->scheduleForDelete($user);
        self::assertFalse($this->uow->isScheduledForInsert($user));
        self::assertFalse($this->uow->isScheduledForUpsert($user));
        self::assertTrue($this->uow->isScheduledForDelete($user));
    }

    public function testThrowsOnPersistOfMappedSuperclass(): void
    {
        $this->expectException(MongoDBException::class);
        $this->uow->persist(new MappedSuperclass());
    }

    public function testParentAssociations(): void
    {
        $a = new ParentAssociationTest('a');
        $b = new ParentAssociationTest('b');
        $c = new ParentAssociationTest('c');
        $d = new ParentAssociationTest('c');

        $this->uow->setParentAssociation($b, ClassMetadataTestUtil::getFieldMapping(['name' => 'b']), $a, 'b');
        $this->uow->setParentAssociation($c, ClassMetadataTestUtil::getFieldMapping(['name' => 'c']), $b, 'b.c');
        $mappingD = ClassMetadataTestUtil::getFieldMapping(['name' => 'c']);
        $this->uow->setParentAssociation($d, $mappingD, $c, 'b.c.d');

        self::assertEquals([$mappingD, $c, 'b.c.d'], $this->uow->getParentAssociation($d));
    }

    #[DoesNotPerformAssertions]
    public function testPreUpdateTriggeredWithEmptyChangeset(): void
    {
        $this->dm->getEventManager()->addEventSubscriber(
            new PreUpdateListenerMock(),
        );
        $user           = new ForumUser();
        $user->username = '12345';

        $this->dm->persist($user);
        $this->dm->flush();

        $user->username = '1234';
        $this->dm->persist($user);
        $this->dm->flush();
    }

    public function testNotSaved(): void
    {
        $test           = new NotSaved();
        $test->name     = 'test';
        $test->notSaved = 'Jon';
        $this->dm->persist($test);

        $this->uow->computeChangeSets();
        $changeset = $this->uow->getDocumentChangeSet($test);
        self::assertArrayNotHasKey('notSaved', $changeset);
    }

    public function testNoUpdatesOnGridFSFields(): void
    {
        $file = new File();

        $access = Closure::bind(function (string $property, $value): void {
            $this->$property = $value;
        }, $file, $file);

        $access('id', 1234);
        $access('filename', 'foo');
        $access('length', 123);
        $access('uploadDate', new DateTime());
        $access('chunkSize', 1234);

        $owner = new User();
        $this->uow->persist($owner);

        $file->getOrCreateMetadata()->setOwner($owner);

        $data = [
            '_id' => 123,
            'filename' => 'file.txt',
            'chunkSize' => 256,
            'length' => 0,
            'uploadDate' => new DateTime(),
        ];

        $this->uow->registerManaged($file, spl_object_hash($file), $data);

        $this->uow->computeChangeSets();
        $changeset = $this->uow->getDocumentChangeSet($file);
        self::assertArrayNotHasKey('filename', $changeset);
        self::assertArrayNotHasKey('chunkSize', $changeset);
        self::assertArrayNotHasKey('length', $changeset);
        self::assertArrayNotHasKey('uploadDate', $changeset);
        self::assertArrayHasKey('metadata', $changeset);
    }

    public function testComputingChangesetForFileWithoutMetadataThrowsNoError(): void
    {
        $file = new FileWithoutMetadata();

        $access = Closure::bind(function (string $property, $value): void {
            $this->$property = $value;
        }, $file, $file);

        $access('filename', 'foo');

        $data = [
            '_id' => 123,
            'filename' => 'file.txt',
        ];

        $this->uow->registerManaged($file, spl_object_hash($file), $data);

        $this->uow->computeChangeSets();
        $changeset = $this->uow->getDocumentChangeSet($file);

        self::assertSame([], $changeset);
    }

    /**
     * @param array<string, mixed>|null $origData
     * @param array<string, mixed>|null $updateData
     */
    #[DataProvider('getScheduleForUpdateWithArraysTests')]
    public function testScheduleForUpdateWithArrays(?array $origData, ?array $updateData, bool $shouldInUpdate): void
    {
        $arrayTest = new ArrayTest($origData);
        $this->uow->persist($arrayTest);
        $this->uow->computeChangeSets();
        $this->uow->commit();

        $arrayTest->data = $updateData;
        $this->uow->computeChangeSets();

        self::assertEquals($shouldInUpdate, $this->uow->isScheduledForUpdate($arrayTest));

        $this->uow->commit();

        self::assertFalse($this->uow->isScheduledForUpdate($arrayTest));
    }

    public static function getScheduleForUpdateWithArraysTests(): array
    {
        return [
            [
                null,
                ['bar' => 'foo'],
                true,
            ],
            [
                ['foo' => 'bar'],
                null,
                true,
            ],
            [
                ['foo' => 'bar'],
                ['bar' => 'foo'],
                true,
            ],
            [
                ['foo' => 'bar'],
                ['foo' => 'foo'],
                true,
            ],
            [
                ['foo' => 'bar'],
                ['foo' => 'bar'],
                false,
            ],
            [
                ['foo' => 'bar'],
                ['foo' => true],
                true,
            ],
            [
                ['foo' => 'bar'],
                ['foo' => 99],
                true,
            ],
            [
                ['foo' => 99],
                ['foo' => true],
                true,
            ],
            [
                ['foo' => true],
                ['foo' => true],
                false,
            ],
        ];
    }

    public function testRegisterManagedEmbeddedDocumentWithMappedIdAndNullValue(): void
    {
        $document = new EmbeddedDocumentWithId();
        $oid      = spl_object_hash($document);

        $this->uow->registerManaged($document, null, []);

        self::assertEquals($oid, $this->uow->getDocumentIdentifier($document));
    }

    public function testRegisterManagedEmbeddedDocumentWithoutMappedId(): void
    {
        $document = new EmbeddedDocumentWithoutId();
        $oid      = spl_object_hash($document);

        $this->uow->registerManaged($document, null, []);

        self::assertEquals($oid, $this->uow->getDocumentIdentifier($document));
    }

    public function testRegisterManagedEmbeddedDocumentWithMappedIdStrategyNoneAndNullValue(): void
    {
        $document = new EmbeddedDocumentWithIdStrategyNone();
        $oid      = spl_object_hash($document);

        $this->uow->registerManaged($document, null, []);

        self::assertEquals($oid, $this->uow->getDocumentIdentifier($document));
    }

    public function testPersistNewGridFSFile(): void
    {
        $file = new File();

        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage(sprintf('Cannot persist GridFS file for class "%s" through UnitOfWork', File::class));

        $this->uow->persist($file);
    }

    public function testPersistRemovedDocument(): void
    {
        $user           = new ForumUser();
        $user->username = 'jwage';

        $this->uow->persist($user);
        $this->uow->commit();

        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($user));

        $this->uow->remove($user);

        self::assertEquals(UnitOfWork::STATE_REMOVED, $this->uow->getDocumentState($user));

        $this->uow->persist($user);

        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($user));

        $this->uow->commit();

        self::assertNotNull($this->dm->getRepository($user::class)->find($user->id));
    }

    public function testRemovePersistedButNotFlushedDocument(): void
    {
        $user           = new ForumUser();
        $user->username = 'jwage';

        $this->uow->persist($user);
        $this->uow->remove($user);
        $this->uow->commit();

        self::assertNull($this->dm->getRepository($user::class)->find($user->id));
    }

    public function testPersistRemovedEmbeddedDocument(): void
    {
        $test           = new PersistRemovedEmbeddedDocument();
        $test->embedded = new EmbeddedDocumentWithId();
        $this->uow->persist($test);
        $this->uow->commit();
        $this->uow->clear();

        $test = $this->dm->getRepository($test::class)->find($test->id);

        $this->uow->remove($test);

        self::assertEquals(UnitOfWork::STATE_REMOVED, $this->uow->getDocumentState($test));
        self::assertTrue($this->uow->isScheduledForDelete($test));

        // removing a top level document should cascade to embedded documents
        self::assertEquals(UnitOfWork::STATE_REMOVED, $this->uow->getDocumentState($test->embedded));
        self::assertTrue($this->uow->isScheduledForDelete($test->embedded));

        $this->uow->persist($test);
        $this->uow->commit();

        self::assertFalse($test->embedded->preRemove);

        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($test));
        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($test->embedded));
    }

    public function testPersistingEmbeddedDocumentWithoutIdentifier(): void
    {
        $address = new Address();
        $user    = new User();
        $user->setAddress($address);

        self::assertEquals(UnitOfWork::STATE_NEW, $this->uow->getDocumentState($address));
        self::assertFalse($this->uow->isInIdentityMap($address));
        self::assertNull($this->uow->getDocumentIdentifier($address));

        $this->uow->persist($user);

        self::assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($user->getAddress()));
        self::assertTrue($this->uow->isInIdentityMap($address));
        self::assertTrue($this->uow->isScheduledForInsert($address));
        self::assertEquals(spl_object_hash($address), $this->uow->getDocumentIdentifier($address));

        $this->uow->commit();

        self::assertTrue($this->uow->isInIdentityMap($address));
        self::assertFalse($this->uow->isScheduledForInsert($address));
    }

    public function testEmbeddedDocumentChangeSets(): void
    {
        $address = new Address();
        $user    = new User();
        $user->setAddress($address);

        $this->uow->persist($user);

        $this->uow->computeChangeSets();

        $changeSet = $this->uow->getDocumentChangeSet($address);
        self::assertNotEmpty($changeSet);

        $this->uow->commit();

        $address->setCity('Nashville');

        $this->uow->computeChangeSets();
        $changeSet = $this->uow->getDocumentChangeSet($address);

        self::assertArrayHasKey('city', $changeSet);
        self::assertEquals('Nashville', $changeSet['city'][1]);
    }

    public function testRecomputeChangesetForUninitializedProxyDoesNotCreateChangeset(): void
    {
        $user           = new ForumUser();
        $user->username = '12345';
        $user->setAvatar(new ForumAvatar());

        $this->dm->persist($user);
        $this->dm->flush();

        $id = $user->getId();
        $this->dm->clear();

        $user = $this->dm->find(ForumUser::class, $id);
        self::assertInstanceOf(ForumUser::class, $user);

        self::assertInstanceOf(GhostObjectInterface::class, $user->getAvatar());

        $classMetadata = $this->dm->getClassMetadata(ForumAvatar::class);

        $this->uow->recomputeSingleDocumentChangeSet($classMetadata, $user->getAvatar());

        self::assertEquals([], $this->uow->getDocumentChangeSet($user->getAvatar()));
    }

    public function testCommitsInProgressIsUpdatedOnException(): void
    {
        $this->dm->getEventManager()->addEventSubscriber(
            new ExceptionThrowingListenerMock(),
        );
        $user           = new ForumUser();
        $user->username = '12345';

        $this->dm->persist($user);

        try {
            $this->dm->flush();
        } catch (Throwable) {
            $getCommitsInProgress = Closure::bind(fn (UnitOfWork $unitOfWork) => $unitOfWork->commitsInProgress, $this->dm->getUnitOfWork(), UnitOfWork::class);

            self::assertSame(0, $getCommitsInProgress($this->dm->getUnitOfWork()));

            return;
        }

        $this->fail('This should never be reached, an exception should have been thrown.');
    }

    public function testTransactionalCommitOmitsWriteConcernInOperation(): void
    {
        $this->skipTestIfNoTransactionSupport();

        // Force transaction config to be enabled
        $this->dm->getConfiguration()->setUseTransactionalFlush(true);

        $collection = $this->createMock(MongoDBCollection::class);
        $collection->expects($this->once())
            ->method('insertMany')
            ->with($this->isType('array'), $this->logicalNot($this->arrayHasKey('writeConcern')));

        $documentPersister = $this->uow->getDocumentPersister(ForumUser::class);

        $reflectionProperty = new ReflectionProperty($documentPersister, 'collection');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($documentPersister, $collection);

        $user           = new ForumUser();
        $user->username = '12345';
        $this->uow->persist($user);

        $this->uow->commit(['writeConcern' => new WriteConcern(1)]);
    }

    public function testTransactionalCommitUsesWriteConcernInCommitCommand(): void
    {
        $this->skipTestIfNoTransactionSupport();

        // Force transaction config to be enabled
        $this->dm->getConfiguration()->setUseTransactionalFlush(true);

        $user           = new ForumUser();
        $user->username = '12345';
        $this->uow->persist($user);

        $logger = new CommandLogger();
        $logger->register();

        $this->uow->commit(['writeConcern' => new WriteConcern('majority')]);

        $logger->unregister();

        $commands      = $logger->getAll();
        $commitCommand = end($commands);

        $this->assertSame('commitTransaction', $commitCommand->getCommandName());
        $this->assertObjectHasProperty('writeConcern', $commitCommand->getCommand());
        $this->assertEquals((object) ['w' => 'majority'], $commitCommand->getCommand()->writeConcern);
    }
}

class ParentAssociationTest
{
    /** @var string */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

#[ODM\Document]
#[ODM\ChangeTrackingPolicy('NOTIFY')]
class NotifyChangedDocument implements NotifyPropertyChanged
{
    /** @var PropertyChangedListener[] */
    private $_listeners = [];

    /** @var int|null */
    #[ODM\Id(type: 'int', strategy: 'none')]
    private $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    private $data;

    /** @var Collection<int, NotifyChangedRelatedItem> */
    #[ODM\ReferenceMany(targetDocument: NotifyChangedRelatedItem::class)]
    private $items;

    /** @var mixed */
    private $transient; // not persisted

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getData(): ?string
    {
        return $this->data;
    }

    public function setData(string $data): void
    {
        if ($data === $this->data) {
            return;
        }

        $this->onPropertyChanged('data', $this->data, $data);
        $this->data = $data;
    }

    /** @return Collection<int, NotifyChangedRelatedItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    /** @param mixed $value */
    public function setTransient($value): void
    {
        if ($value === $this->transient) {
            return;
        }

        $this->onPropertyChanged('transient', $this->transient, $value);
        $this->transient = $value;
    }

    public function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        $this->_listeners[] = $listener;
    }

    /**
     * @param mixed $oldValue
     * @param mixed $newValue
     */
    protected function onPropertyChanged(string $propName, $oldValue, $newValue): void
    {
        foreach ($this->_listeners as $listener) {
            $listener->propertyChanged($this, $propName, $oldValue, $newValue);
        }
    }
}

#[ODM\Document]
class NotifyChangedRelatedItem
{
    /** @var int|null */
    #[ODM\Id(type: 'int', strategy: 'none')]
    private $id;

    /** @var NotifyChangedDocument|null */
    #[ODM\ReferenceOne(targetDocument: NotifyChangedDocument::class)]
    private $owner;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getOwner(): ?NotifyChangedDocument
    {
        return $this->owner;
    }

    public function setOwner(NotifyChangedDocument $owner): void
    {
        $this->owner = $owner;
    }
}

#[ODM\Document]
class ArrayTest
{
    /** @var string|null */
    #[ODM\Id]
    private $id;

    /** @var array<string, mixed>|null */
    #[ODM\Field(type: 'hash')]
    public $data;

    /** @param array<string, mixed>|null $data */
    public function __construct(?array $data)
    {
        $this->data = $data;
    }
}

#[ODM\Document]
class UowCustomIdDocument
{
    /** @var string|null */
    #[ODM\Id(type: 'custom_id')]
    public $id;
}

#[ODM\EmbeddedDocument]
class EmbeddedUpsertDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
}

#[ODM\EmbeddedDocument]
class EmbeddedDocumentWithoutId
{
}

#[ODM\EmbeddedDocument]
class EmbeddedDocumentWithId
{
    /** @var bool */
    public $preRemove = false;

    /** @var string|null */
    #[ODM\Id]
    public $id;

    #[ODM\PreRemove]
    public function preRemove(): void
    {
        $this->preRemove = true;
    }
}

#[ODM\EmbeddedDocument]
class EmbeddedDocumentWithIdStrategyNone
{
    /** @var string|null */
    #[ODM\Id(strategy: 'none')]
    public $id;
}

#[ODM\Document]
class PersistRemovedEmbeddedDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var EmbeddedDocumentWithId */
    #[ODM\EmbedOne(targetDocument: EmbeddedDocumentWithId::class)]
    public $embedded;
}

#[ODM\MappedSuperclass]
class MappedSuperclass
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
}
