<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Closure;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
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
use ProxyManager\Proxy\GhostObjectInterface;
use Throwable;

use function get_class;
use function spl_object_hash;
use function sprintf;

class UnitOfWorkTest extends BaseTest
{
    public function testIsDocumentScheduled()
    {
        $class = $this->dm->getClassMetadata(ForumUser::class);
        $user  = new ForumUser();
        $this->assertFalse($this->uow->isDocumentScheduled($user));
        $this->uow->scheduleForInsert($class, $user);
        $this->assertTrue($this->uow->isDocumentScheduled($user));
    }

    public function testScheduleForInsert()
    {
        $class = $this->dm->getClassMetadata(ForumUser::class);
        $user  = new ForumUser();
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->uow->scheduleForInsert($class, $user);
        $this->assertTrue($this->uow->isScheduledForInsert($user));
    }

    public function testScheduleForUpsert()
    {
        $class    = $this->dm->getClassMetadata(ForumUser::class);
        $user     = new ForumUser();
        $user->id = new ObjectId();
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->assertFalse($this->uow->isScheduledForUpsert($user));
        $this->uow->scheduleForUpsert($class, $user);
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->assertTrue($this->uow->isScheduledForUpsert($user));
    }

    public function testGetScheduledDocumentUpserts()
    {
        $class    = $this->dm->getClassMetadata(ForumUser::class);
        $user     = new ForumUser();
        $user->id = new ObjectId();
        $this->assertEmpty($this->uow->getScheduledDocumentUpserts());
        $this->uow->scheduleForUpsert($class, $user);
        $this->assertEquals([spl_object_hash($user) => $user], $this->uow->getScheduledDocumentUpserts());
    }

    public function testScheduleForEmbeddedUpsert()
    {
        $test     = new EmbeddedUpsertDocument();
        $test->id = (string) new ObjectId();
        $this->assertFalse($this->uow->isScheduledForInsert($test));
        $this->assertFalse($this->uow->isScheduledForUpsert($test));
        $this->uow->persist($test);
        $this->assertTrue($this->uow->isScheduledForInsert($test));
        $this->assertFalse($this->uow->isScheduledForUpsert($test));
    }

    public function testScheduleForUpsertWithNonObjectIdValues()
    {
        $doc     = new UowCustomIdDocument();
        $doc->id = 'string';
        $class   = $this->dm->getClassMetadata(get_class($doc));
        $this->assertFalse($this->uow->isScheduledForInsert($doc));
        $this->assertFalse($this->uow->isScheduledForUpsert($doc));
        $this->uow->scheduleForUpsert($class, $doc);
        $this->assertFalse($this->uow->isScheduledForInsert($doc));
        $this->assertTrue($this->uow->isScheduledForUpsert($doc));
    }

    public function testScheduleForInsertShouldNotUpsertDocumentsWithInconsistentIdValues()
    {
        $class    = $this->dm->getClassMetadata(ForumUser::class);
        $user     = new ForumUser();
        $user->id = 1;
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->assertFalse($this->uow->isScheduledForUpsert($user));
        $this->uow->scheduleForInsert($class, $user);
        $this->assertTrue($this->uow->isScheduledForInsert($user));
        $this->assertFalse($this->uow->isScheduledForUpsert($user));
    }

    public function testRegisterRemovedOnNewEntityIsIgnored()
    {
        $user           = new ForumUser();
        $user->username = 'romanb';
        $this->assertFalse($this->uow->isScheduledForDelete($user));
        $this->uow->scheduleForDelete($user);
        $this->assertFalse($this->uow->isScheduledForDelete($user));
    }

    public function testScheduleForDeleteShouldUnregisterScheduledUpserts()
    {
        $class    = $this->dm->getClassMetadata(ForumUser::class);
        $user     = new ForumUser();
        $user->id = new ObjectId();
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->assertFalse($this->uow->isScheduledForUpsert($user));
        $this->assertFalse($this->uow->isScheduledForDelete($user));
        $this->uow->scheduleForUpsert($class, $user);
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->assertTrue($this->uow->isScheduledForUpsert($user));
        $this->assertFalse($this->uow->isScheduledForDelete($user));
        $this->uow->scheduleForDelete($user);
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->assertFalse($this->uow->isScheduledForUpsert($user));
        $this->assertTrue($this->uow->isScheduledForDelete($user));
    }

    public function testThrowsOnPersistOfMappedSuperclass()
    {
        $this->expectException(MongoDBException::class);
        $this->uow->persist(new MappedSuperclass());
    }

    public function testParentAssociations()
    {
        $a = new ParentAssociationTest('a');
        $b = new ParentAssociationTest('b');
        $c = new ParentAssociationTest('c');
        $d = new ParentAssociationTest('c');

        $this->uow->setParentAssociation($b, ClassMetadataTestUtil::getFieldMapping(['name' => 'b']), $a, 'b');
        $this->uow->setParentAssociation($c, ClassMetadataTestUtil::getFieldMapping(['name' => 'c']), $b, 'b.c');
        $mappingD = ClassMetadataTestUtil::getFieldMapping(['name' => 'c']);
        $this->uow->setParentAssociation($d, $mappingD, $c, 'b.c.d');

        $this->assertEquals([$mappingD, $c, 'b.c.d'], $this->uow->getParentAssociation($d));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testPreUpdateTriggeredWithEmptyChangeset()
    {
        $this->dm->getEventManager()->addEventSubscriber(
            new PreUpdateListenerMock()
        );
        $user           = new ForumUser();
        $user->username = '12345';

        $this->dm->persist($user);
        $this->dm->flush();

        $user->username = '1234';
        $this->dm->persist($user);
        $this->dm->flush();
    }

    public function testNotSaved()
    {
        $test           = new NotSaved();
        $test->name     = 'test';
        $test->notSaved = 'Jon';
        $this->dm->persist($test);

        $this->uow->computeChangeSets();
        $changeset = $this->uow->getDocumentChangeSet($test);
        $this->assertArrayNotHasKey('notSaved', $changeset);
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
        $this->assertArrayNotHasKey('filename', $changeset);
        $this->assertArrayNotHasKey('chunkSize', $changeset);
        $this->assertArrayNotHasKey('length', $changeset);
        $this->assertArrayNotHasKey('uploadDate', $changeset);
        $this->assertArrayHasKey('metadata', $changeset);
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

        $this->assertSame([], $changeset);
    }

    /**
     * @dataProvider getScheduleForUpdateWithArraysTests
     */
    public function testScheduleForUpdateWithArrays($origData, $updateData, $shouldInUpdate)
    {
        $arrayTest = new ArrayTest($origData);
        $this->uow->persist($arrayTest);
        $this->uow->computeChangeSets();
        $this->uow->commit();

        $arrayTest->data = $updateData;
        $this->uow->computeChangeSets();

        $this->assertEquals($shouldInUpdate, $this->uow->isScheduledForUpdate($arrayTest));

        $this->uow->commit();

        $this->assertFalse($this->uow->isScheduledForUpdate($arrayTest));
    }

    public function getScheduleForUpdateWithArraysTests()
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

    public function testRegisterManagedEmbeddedDocumentWithMappedIdAndNullValue()
    {
        $document = new EmbeddedDocumentWithId();
        $oid      = spl_object_hash($document);

        $this->uow->registerManaged($document, null, []);

        $this->assertEquals($oid, $this->uow->getDocumentIdentifier($document));
    }

    public function testRegisterManagedEmbeddedDocumentWithoutMappedId()
    {
        $document = new EmbeddedDocumentWithoutId();
        $oid      = spl_object_hash($document);

        $this->uow->registerManaged($document, null, []);

        $this->assertEquals($oid, $this->uow->getDocumentIdentifier($document));
    }

    public function testRegisterManagedEmbeddedDocumentWithMappedIdStrategyNoneAndNullValue()
    {
        $document = new EmbeddedDocumentWithIdStrategyNone();
        $oid      = spl_object_hash($document);

        $this->uow->registerManaged($document, null, []);

        $this->assertEquals($oid, $this->uow->getDocumentIdentifier($document));
    }

    public function testPersistNewGridFSFile(): void
    {
        $file = new File();

        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage(sprintf('Cannot persist GridFS file for class "%s" through UnitOfWork', File::class));

        $this->uow->persist($file);
    }

    public function testPersistRemovedDocument()
    {
        $user           = new ForumUser();
        $user->username = 'jwage';

        $this->uow->persist($user);
        $this->uow->commit();

        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($user));

        $this->uow->remove($user);

        $this->assertEquals(UnitOfWork::STATE_REMOVED, $this->uow->getDocumentState($user));

        $this->uow->persist($user);

        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($user));

        $this->uow->commit();

        $this->assertNotNull($this->dm->getRepository(get_class($user))->find($user->id));
    }

    public function testRemovePersistedButNotFlushedDocument()
    {
        $user           = new ForumUser();
        $user->username = 'jwage';

        $this->uow->persist($user);
        $this->uow->remove($user);
        $this->uow->commit();

        $this->assertNull($this->dm->getRepository(get_class($user))->find($user->id));
    }

    public function testPersistRemovedEmbeddedDocument()
    {
        $test           = new PersistRemovedEmbeddedDocument();
        $test->embedded = new EmbeddedDocumentWithId();
        $this->uow->persist($test);
        $this->uow->commit();
        $this->uow->clear();

        $test = $this->dm->getRepository(get_class($test))->find($test->id);

        $this->uow->remove($test);

        $this->assertEquals(UnitOfWork::STATE_REMOVED, $this->uow->getDocumentState($test));
        $this->assertTrue($this->uow->isScheduledForDelete($test));

        // removing a top level document should cascade to embedded documents
        $this->assertEquals(UnitOfWork::STATE_REMOVED, $this->uow->getDocumentState($test->embedded));
        $this->assertTrue($this->uow->isScheduledForDelete($test->embedded));

        $this->uow->persist($test);
        $this->uow->commit();

        $this->assertFalse($test->embedded->preRemove);

        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($test));
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($test->embedded));
    }

    public function testPersistingEmbeddedDocumentWithoutIdentifier()
    {
        $address = new Address();
        $user    = new User();
        $user->setAddress($address);

        $this->assertEquals(UnitOfWork::STATE_NEW, $this->uow->getDocumentState($address));
        $this->assertFalse($this->uow->isInIdentityMap($address));
        $this->assertNull($this->uow->getDocumentIdentifier($address));

        $this->uow->persist($user);

        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($user->getAddress()));
        $this->assertTrue($this->uow->isInIdentityMap($address));
        $this->assertTrue($this->uow->isScheduledForInsert($address));
        $this->assertEquals(spl_object_hash($address), $this->uow->getDocumentIdentifier($address));

        $this->uow->commit();

        $this->assertTrue($this->uow->isInIdentityMap($address));
        $this->assertFalse($this->uow->isScheduledForInsert($address));
    }

    public function testEmbeddedDocumentChangeSets()
    {
        $address = new Address();
        $user    = new User();
        $user->setAddress($address);

        $this->uow->persist($user);

        $this->uow->computeChangeSets();

        $changeSet = $this->uow->getDocumentChangeSet($address);
        $this->assertNotEmpty($changeSet);

        $this->uow->commit();

        $address->setCity('Nashville');

        $this->uow->computeChangeSets();
        $changeSet = $this->uow->getDocumentChangeSet($address);

        $this->assertArrayHasKey('city', $changeSet);
        $this->assertEquals('Nashville', $changeSet['city'][1]);
    }

    public function testGetClassNameForAssociation()
    {
        $mapping = ClassMetadataTestUtil::getFieldMapping([
            'discriminatorField' => 'type',
            'discriminatorMap' => ['forum_user' => ForumUser::class],
            'targetDocument' => User::class,
        ]);
        $data    = ['type' => 'forum_user'];

        $this->assertEquals(ForumUser::class, $this->uow->getClassNameForAssociation($mapping, $data));
    }

    public function testGetClassNameForAssociationWithClassMetadataDiscriminatorMap()
    {
        $mapping = ClassMetadataTestUtil::getFieldMapping(['targetDocument' => User::class]);
        $data    = ['type' => 'forum_user'];

        $userClassMetadata                     = new ClassMetadata(ForumUser::class);
        $userClassMetadata->discriminatorField = 'type';
        $userClassMetadata->discriminatorMap   = ['forum_user' => ForumUser::class];
        $this->dm->getMetadataFactory()->setMetadataFor(User::class, $userClassMetadata);

        $this->assertEquals(ForumUser::class, $this->uow->getClassNameForAssociation($mapping, $data));
    }

    public function testGetClassNameForAssociationReturnsTargetDocumentWithNullData()
    {
        $mapping = ClassMetadataTestUtil::getFieldMapping(['targetDocument' => User::class]);
        $this->assertEquals(User::class, $this->uow->getClassNameForAssociation($mapping, null));
    }

    public function testRecomputeChangesetForUninitializedProxyDoesNotCreateChangeset()
    {
        $user           = new ForumUser();
        $user->username = '12345';
        $user->setAvatar(new ForumAvatar());

        $this->dm->persist($user);
        $this->dm->flush();

        $id = $user->getId();
        $this->dm->clear();

        $user = $this->dm->find(ForumUser::class, $id);
        $this->assertInstanceOf(ForumUser::class, $user);

        $this->assertInstanceOf(GhostObjectInterface::class, $user->getAvatar());

        $classMetadata = $this->dm->getClassMetadata(ForumAvatar::class);

        $this->uow->recomputeSingleDocumentChangeSet($classMetadata, $user->getAvatar());

        $this->assertEquals([], $this->uow->getDocumentChangeSet($user->getAvatar()));
    }

    public function testCommitsInProgressIsUpdatedOnException()
    {
        $this->dm->getEventManager()->addEventSubscriber(
            new ExceptionThrowingListenerMock()
        );
        $user           = new ForumUser();
        $user->username = '12345';

        $this->dm->persist($user);

        try {
            $this->dm->flush();
        } catch (Throwable $exception) {
            $getCommitsInProgress = Closure::bind(function (UnitOfWork $unitOfWork) {
                /** @psalm-suppress InaccessibleProperty */
                return $unitOfWork->commitsInProgress;
            }, $this->dm->getUnitOfWork(), UnitOfWork::class);

            $this->assertSame(0, $getCommitsInProgress($this->dm->getUnitOfWork()));

            return;
        }

        $this->fail('This should never be reached, an exception should have been thrown.');
    }
}

class ParentAssociationTest
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/**
 * @ODM\Document
 * @ODM\ChangeTrackingPolicy("NOTIFY")
 */
class NotifyChangedDocument implements NotifyPropertyChanged
{
    private $_listeners = [];

    /** @ODM\Id(type="int", strategy="none") */
    private $id;

    /** @ODM\Field(type="string") */
    private $data;

    /** @ODM\ReferenceMany(targetDocument=NotifyChangedRelatedItem::class) */
    private $items;

    private $transient; // not persisted

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        if ($data === $this->data) {
            return;
        }

        $this->onPropertyChanged('data', $this->data, $data);
        $this->data = $data;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function setTransient($value)
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

    protected function onPropertyChanged($propName, $oldValue, $newValue)
    {
        foreach ($this->_listeners as $listener) {
            $listener->propertyChanged($this, $propName, $oldValue, $newValue);
        }
    }
}

/** @ODM\Document */
class NotifyChangedRelatedItem
{
    /** @ODM\Id(type="int", strategy="none") */
    private $id;

    /** @ODM\ReferenceOne(targetDocument=NotifyChangedDocument::class) */
    private $owner;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }
}

/** @ODM\Document */
class ArrayTest
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="hash") */
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
}

/** @ODM\Document */
class UowCustomIdDocument
{
    /** @ODM\Id(type="custom_id") */
    public $id;
}

/** @ODM\EmbeddedDocument */
class EmbeddedUpsertDocument
{
    /** @ODM\Id */
    public $id;
}

/** @ODM\EmbeddedDocument */
class EmbeddedDocumentWithoutId
{
}

/** @ODM\EmbeddedDocument */
class EmbeddedDocumentWithId
{
    public $preRemove = false;

    /** @ODM\Id */
    public $id;

    /** @ODM\PreRemove */
    public function preRemove()
    {
        $this->preRemove = true;
    }
}

/** @ODM\EmbeddedDocument */
class EmbeddedDocumentWithIdStrategyNone
{
    /** @ODM\Id(strategy="none") */
    public $id;
}

/** @ODM\Document */
class PersistRemovedEmbeddedDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument=EmbeddedDocumentWithId::class) */
    public $embedded;
}

/** @ODM\MappedSuperclass */
class MappedSuperclass
{
    /** @ODM\Id */
    public $id;
}
