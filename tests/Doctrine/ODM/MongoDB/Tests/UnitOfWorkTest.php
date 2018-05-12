<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\EventManager;
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Hydrator\HydratorFactory;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder;
use Doctrine\ODM\MongoDB\Proxy\Proxy;
use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentPersisterMock;
use Doctrine\ODM\MongoDB\Tests\Mocks\ExceptionThrowingListenerMock;
use Doctrine\ODM\MongoDB\Tests\Mocks\PreUpdateListenerMock;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Documents\Address;
use Documents\CmsPhonenumber;
use Documents\File;
use Documents\FileWithoutMetadata;
use Documents\ForumAvatar;
use Documents\ForumUser;
use Documents\Functional\NotSaved;
use Documents\User;
use MongoDB\BSON\ObjectId;
use function get_class;
use function spl_object_hash;
use function sprintf;
use function ucfirst;

class UnitOfWorkTest extends BaseTest
{
    public function testIsDocumentScheduled()
    {
        $class = $this->dm->getClassMetadata(ForumUser::class);
        $user = new ForumUser();
        $this->assertFalse($this->uow->isDocumentScheduled($user));
        $this->uow->scheduleForInsert($class, $user);
        $this->assertTrue($this->uow->isDocumentScheduled($user));
    }

    public function testScheduleForInsert()
    {
        $class = $this->dm->getClassMetadata(ForumUser::class);
        $user = new ForumUser();
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->uow->scheduleForInsert($class, $user);
        $this->assertTrue($this->uow->isScheduledForInsert($user));
    }

    public function testScheduleForUpsert()
    {
        $class = $this->dm->getClassMetadata(ForumUser::class);
        $user = new ForumUser();
        $user->id = new ObjectId();
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->assertFalse($this->uow->isScheduledForUpsert($user));
        $this->uow->scheduleForUpsert($class, $user);
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->assertTrue($this->uow->isScheduledForUpsert($user));
    }

    public function testGetScheduledDocumentUpserts()
    {
        $class = $this->dm->getClassMetadata(ForumUser::class);
        $user = new ForumUser();
        $user->id = new ObjectId();
        $this->assertEmpty($this->uow->getScheduledDocumentUpserts());
        $this->uow->scheduleForUpsert($class, $user);
        $this->assertEquals([spl_object_hash($user) => $user], $this->uow->getScheduledDocumentUpserts());
    }

    public function testScheduleForEmbeddedUpsert()
    {
        $class = $this->dm->getClassMetadata(ForumUser::class);
        $test = new EmbeddedUpsertDocument();
        $test->id = (string) new ObjectId();
        $this->assertFalse($this->uow->isScheduledForInsert($test));
        $this->assertFalse($this->uow->isScheduledForUpsert($test));
        $this->uow->persist($test);
        $this->assertTrue($this->uow->isScheduledForInsert($test));
        $this->assertFalse($this->uow->isScheduledForUpsert($test));
    }

    public function testScheduleForUpsertWithNonObjectIdValues()
    {
        $doc = new UowCustomIdDocument();
        $doc->id = 'string';
        $class = $this->dm->getClassMetadata(get_class($doc));
        $this->assertFalse($this->uow->isScheduledForInsert($doc));
        $this->assertFalse($this->uow->isScheduledForUpsert($doc));
        $this->uow->scheduleForUpsert($class, $doc);
        $this->assertFalse($this->uow->isScheduledForInsert($doc));
        $this->assertTrue($this->uow->isScheduledForUpsert($doc));
    }

    public function testScheduleForInsertShouldNotUpsertDocumentsWithInconsistentIdValues()
    {
        $class = $this->dm->getClassMetadata(ForumUser::class);
        $user = new ForumUser();
        $user->id = 1;
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->assertFalse($this->uow->isScheduledForUpsert($user));
        $this->uow->scheduleForInsert($class, $user);
        $this->assertTrue($this->uow->isScheduledForInsert($user));
        $this->assertFalse($this->uow->isScheduledForUpsert($user));
    }

    public function testRegisterRemovedOnNewEntityIsIgnored()
    {
        $user = new ForumUser();
        $user->username = 'romanb';
        $this->assertFalse($this->uow->isScheduledForDelete($user));
        $this->uow->scheduleForDelete($user);
        $this->assertFalse($this->uow->isScheduledForDelete($user));
    }

    public function testSavingSingleDocumentWithIdentityFieldForcesInsert()
    {
        // Setup fake persister and id generator for identity generation
        $pb = $this->getMockPersistenceBuilder();
        $class = $this->dm->getClassMetadata(ForumUser::class);
        $userPersister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister(ForumUser::class, $userPersister);

        // Test
        $user = new ForumUser();
        $user->username = 'romanb';
        $this->uow->persist($user);

        // Check
        $this->assertCount(0, $userPersister->getInserts());
        $this->assertCount(0, $userPersister->getUpdates());
        $this->assertCount(0, $userPersister->getDeletes());
        $this->assertTrue($this->uow->isInIdentityMap($user));
        // should no longer be scheduled for insert
        $this->assertTrue($this->uow->isScheduledForInsert($user));

        // Now lets check whether a subsequent commit() does anything
        $userPersister->reset();

        // Test
        $this->uow->commit();

        // Check.
        $this->assertCount(1, $userPersister->getInserts());
        $this->assertCount(0, $userPersister->getUpdates());
        $this->assertCount(0, $userPersister->getDeletes());

        // should have an id
        $this->assertNotNull($user->id);
    }

    /**
     * Tests a scenario where a save() operation is cascaded from a ForumUser
     * to its associated ForumAvatar, both entities using IDENTITY id generation.
     */
    public function testCascadedIdentityColumnInsert()
    {
        // Setup fake persister and id generator for identity generation
        //ForumUser
        $pb = $this->getMockPersistenceBuilder();
        $class = $this->dm->getClassMetadata(ForumUser::class);
        $userPersister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister(ForumUser::class, $userPersister);

        // ForumAvatar
        $pb = $this->getMockPersistenceBuilder();
        $class = $this->dm->getClassMetadata(ForumAvatar::class);
        $avatarPersister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister(ForumAvatar::class, $avatarPersister);

        // Test
        $user = new ForumUser();
        $user->username = 'romanb';
        $avatar = new ForumAvatar();
        $user->avatar = $avatar;
        $this->uow->persist($user); // save cascaded to avatar

        $this->uow->commit();

        $this->assertNotNull($user->id);
        $this->assertNotNull($avatar->id);

        $this->assertCount(1, $userPersister->getInserts());
        $this->assertCount(0, $userPersister->getUpdates());
        $this->assertCount(0, $userPersister->getDeletes());

        $this->assertCount(1, $avatarPersister->getInserts());
        $this->assertCount(0, $avatarPersister->getUpdates());
        $this->assertCount(0, $avatarPersister->getDeletes());
    }

    public function testChangeTrackingNotify()
    {
        $pb = $this->getMockPersistenceBuilder();

        $class = $this->dm->getClassMetadata(NotifyChangedDocument::class);
        $persister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister($class->name, $persister);

        $class = $this->dm->getClassMetadata(NotifyChangedRelatedItem::class);
        $itemPersister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister($class->name, $itemPersister);

        $entity = new NotifyChangedDocument();
        $entity->setId(1);
        $entity->setData('thedata');

        $this->uow->persist($entity);
        $this->uow->commit();

        $this->assertCount(1, $persister->getUpserts());
        $this->assertTrue($this->uow->isInIdentityMap($entity));
        $this->assertFalse($this->uow->isScheduledForDirtyCheck($entity));

        $persister->reset();

        $entity->setData('newdata');
        $entity->setTransient('newtransientvalue');

        $this->assertTrue($this->uow->isScheduledForDirtyCheck($entity));
        $this->assertEquals(['data' => ['thedata', 'newdata']], $this->uow->getDocumentChangeSet($entity));

        $item = new NotifyChangedRelatedItem();
        $item->setId(1);
        $entity->getItems()->add($item);
        $item->setOwner($entity);

        $this->uow->persist($item);
        $this->uow->commit();

        $this->assertCount(1, $itemPersister->getUpserts());
        $this->assertTrue($this->uow->isInIdentityMap($item));
        $this->assertFalse($this->uow->isScheduledForDirtyCheck($item));

        $persister->reset();
        $itemPersister->reset();

        $entity->getItems()->removeElement($item);
        $item->setOwner(null);

        $this->assertTrue($entity->getItems()->isDirty());

        $this->uow->commit();

        $updates = $itemPersister->getUpdates();

        $this->assertCount(1, $updates);
        $this->assertSame($updates[0], $item);
    }

    public function testGetDocumentStateWithAssignedIdentity()
    {
        $pb = $this->getMockPersistenceBuilder();
        $class = $this->dm->getClassMetadata(CmsPhonenumber::class);
        $persister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister(CmsPhonenumber::class, $persister);

        $ph = new CmsPhonenumber();
        $ph->phonenumber = '12345';

        $this->assertEquals(UnitOfWork::STATE_NEW, $this->uow->getDocumentState($ph));
        $this->assertTrue($persister->isExistsCalled());

        $persister->reset();

        // if the document is already managed the exists() check should be skipped
        $this->uow->registerManaged($ph, '12345', []);
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($ph));
        $this->assertFalse($persister->isExistsCalled());
        $ph2 = new CmsPhonenumber();
        $ph2->phonenumber = '12345';
        $this->assertEquals(UnitOfWork::STATE_DETACHED, $this->uow->getDocumentState($ph2));
        $this->assertFalse($persister->isExistsCalled());
    }

    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testThrowsOnPersistOfMappedSuperclass()
    {
        $documentManager = $this->getDocumentManager();
        $documentManager->setClassMetadata(Address::class, $this->getClassMetadata(Address::class, 'MappedSuperclass'));
        $unitOfWork = $this->getUnitOfWork($documentManager);
        $unitOfWork->persist(new Address());
    }

    public function testParentAssociations()
    {
        $a = new ParentAssociationTest('a');
        $b = new ParentAssociationTest('b');
        $c = new ParentAssociationTest('c');
        $d = new ParentAssociationTest('c');

        $documentManager = $this->getDocumentManager();
        $unitOfWork = $this->getUnitOfWork($documentManager);
        $unitOfWork->setParentAssociation($b, ['name' => 'b'], $a, 'b');
        $unitOfWork->setParentAssociation($c, ['name' => 'c'], $b, 'b.c');
        $unitOfWork->setParentAssociation($d, ['name' => 'd'], $c, 'b.c.d');

        $this->assertEquals([['name' => 'd'], $c, 'b.c.d'], $unitOfWork->getParentAssociation($d));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testPreUpdateTriggeredWithEmptyChangeset()
    {
        $this->dm->getEventManager()->addEventSubscriber(
            new PreUpdateListenerMock()
        );
        $user = new ForumUser();
        $user->username = '12345';

        $this->dm->persist($user);
        $this->dm->flush();

        $user->username = '1234';
        $this->dm->persist($user);
        $this->dm->flush();
    }

    public function testNotSaved()
    {
        $test = new NotSaved();
        $test->name = 'test';
        $test->notSaved = 'Jon';
        $this->dm->persist($test);

        $this->uow->computeChangeSets();
        $changeset = $this->uow->getDocumentChangeSet($test);
        $this->assertArrayNotHasKey('notSaved', $changeset);
    }

    public function testNoUpdatesOnGridFSFields(): void
    {
        $file = new File();

        $access = \Closure::bind(function (string $property, $value): void {
            $this->$property = $value;
        }, $file, $file);

        $access('id', 1234);
        $access('filename', 'foo');
        $access('length', 123);
        $access('uploadDate', new \DateTime());
        $access('chunkSize', 1234);

        $owner = new User();
        $this->uow->persist($owner);

        $file->getOrCreateMetadata()->setOwner($owner);

        $data = [
            '_id' => 123,
            'filename' => 'file.txt',
            'chunkSize' => 256,
            'length' => 0,
            'uploadDate' => new \DateTime(),
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

        $access = \Closure::bind(function (string $property, $value): void {
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
        $pb = $this->getMockPersistenceBuilder();
        $class = $this->dm->getClassMetadata(ArrayTest::class);
        $persister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister(ArrayTest::class, $persister);

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
        $oid = spl_object_hash($document);

        $this->uow->registerManaged($document, null, []);

        $this->assertEquals($oid, $this->uow->getDocumentIdentifier($document));
    }

    public function testRegisterManagedEmbeddedDocumentWithoutMappedId()
    {
        $document = new EmbeddedDocumentWithoutId();
        $oid = spl_object_hash($document);

        $this->uow->registerManaged($document, null, []);

        $this->assertEquals($oid, $this->uow->getDocumentIdentifier($document));
    }

    public function testRegisterManagedEmbeddedDocumentWithMappedIdStrategyNoneAndNullValue()
    {
        $document = new EmbeddedDocumentWithIdStrategyNone();
        $oid = spl_object_hash($document);

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
        $user = new ForumUser();
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
        $user = new ForumUser();
        $user->username = 'jwage';

        $this->uow->persist($user);
        $this->uow->remove($user);
        $this->uow->commit();

        $this->assertNull($this->dm->getRepository(get_class($user))->find($user->id));
    }

    public function testPersistRemovedEmbeddedDocument()
    {
        $test = new PersistRemovedEmbeddedDocument();
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
        $user = new User();
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
        $user = new User();
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
        $mapping = [
            'discriminatorField' => 'type',
            'discriminatorMap' => ['forum_user' => ForumUser::class],
            'targetDocument' => User::class,
        ];
        $data = ['type' => 'forum_user'];

        $this->assertEquals(ForumUser::class, $this->uow->getClassNameForAssociation($mapping, $data));
    }

    public function testGetClassNameForAssociationWithClassMetadataDiscriminatorMap()
    {
        $dm = $this->getMockDocumentManager();
        $uow = new UnitOfWork($dm, $this->getMockEventManager(), $this->getMockHydratorFactory());

        $mapping = ['targetDocument' => User::class];
        $data = ['type' => 'forum_user'];

        $userClassMetadata = new ClassMetadata(ForumUser::class);
        $userClassMetadata->discriminatorField = 'type';
        $userClassMetadata->discriminatorMap = ['forum_user' => ForumUser::class];

        $dm->expects($this->once())
            ->method('getClassMetadata')
            ->with(User::class)
            ->will($this->returnValue($userClassMetadata));

        $this->assertEquals(ForumUser::class, $uow->getClassNameForAssociation($mapping, $data));
    }

    public function testGetClassNameForAssociationReturnsTargetDocumentWithNullData()
    {
        $mapping = ['targetDocument' => User::class];
        $this->assertEquals(User::class, $this->uow->getClassNameForAssociation($mapping, null));
    }

    public function testRecomputeChangesetForUninitializedProxyDoesNotCreateChangeset()
    {
        $user = new ForumUser();
        $user->username = '12345';
        $user->setAvatar(new ForumAvatar());

        $this->dm->persist($user);
        $this->dm->flush();

        $id = $user->getId();
        $this->dm->clear();

        $user = $this->dm->find(ForumUser::class, $id);
        $this->assertInstanceOf(ForumUser::class, $user);

        $this->assertInstanceOf(Proxy::class, $user->getAvatar());

        $classMetadata = $this->dm->getClassMetadata(ForumAvatar::class);

        $this->uow->recomputeSingleDocumentChangeSet($classMetadata, $user->getAvatar());

        $this->assertEquals([], $this->uow->getDocumentChangeSet($user->getAvatar()));
    }

    public function testCommitsInProgressIsUpdatedOnException()
    {
        $this->dm->getEventManager()->addEventSubscriber(
            new ExceptionThrowingListenerMock()
        );
        $user = new ForumUser();
        $user->username = '12345';

        $this->dm->persist($user);

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('This should not happen');

        $this->dm->flush();
        $this->assertAttributeSame(0, 'commitsInProgress', $this->dm->getUnitOfWork());
    }


    protected function getDocumentManager()
    {
        return new \Stubs\DocumentManager();
    }

    protected function getUnitOfWork(DocumentManager $dm)
    {
        return new UnitOfWork($dm, $this->getMockEventManager(), $this->getMockHydratorFactory());
    }

    /**
     * Gets mock HydratorFactory instance
     *
     * @return HydratorFactory
     */
    private function getMockHydratorFactory()
    {
        return $this->createMock(HydratorFactory::class);
    }

    /**
     * Gets mock EventManager instance
     *
     * @return EventManager
     */
    private function getMockEventManager()
    {
        return $this->createMock(EventManager::class);
    }

    private function getMockPersistenceBuilder()
    {
        return $this->createMock(PersistenceBuilder::class);
    }

    private function getMockDocumentManager()
    {
        return $this->createMock(DocumentManager::class);
    }

    private function getMockDocumentPersister(PersistenceBuilder $pb, ClassMetadata $class)
    {
        return new DocumentPersisterMock($pb, $this->dm, $this->uow, $this->dm->getHydratorFactory(), $class);
    }

    protected function getClassMetadata($class, $flag)
    {
        $classMetadata = new ClassMetadata($class);
        $classMetadata->{'is' . ucfirst($flag)} = true;
        return $classMetadata;
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

    /** @ODM\Id(type="int_id", strategy="none") */
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

        $this->_onPropertyChanged('data', $this->data, $data);
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

        $this->_onPropertyChanged('transient', $this->transient, $value);
        $this->transient = $value;
    }

    public function addPropertyChangedListener(PropertyChangedListener $listener)
    {
        $this->_listeners[] = $listener;
    }

    protected function _onPropertyChanged($propName, $oldValue, $newValue)
    {
        foreach ($this->_listeners as $listener) {
            $listener->propertyChanged($this, $propName, $oldValue, $newValue);
        }
    }
}

/** @ODM\Document */
class NotifyChangedRelatedItem
{
    /** @ODM\Id(type="int_id", strategy="none") */
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
