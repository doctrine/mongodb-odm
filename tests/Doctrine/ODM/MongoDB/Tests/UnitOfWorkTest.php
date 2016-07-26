<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\PropertyChangedListener;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder;
use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentPersisterMock;
use Documents\ForumUser;
use Documents\ForumAvatar;

class UnitOfWorkTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testIsDocumentScheduled()
    {
        $class = $this->dm->getClassMetadata('Documents\ForumUser');
        $user = new ForumUser();
        $this->assertFalse($this->uow->isDocumentScheduled($user));
        $this->uow->scheduleForInsert($class, $user);
        $this->assertTrue($this->uow->isDocumentScheduled($user));
    }

    public function testScheduleForInsert()
    {
        $class = $this->dm->getClassMetadata('Documents\ForumUser');
        $user = new ForumUser();
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->uow->scheduleForInsert($class, $user);
        $this->assertTrue($this->uow->isScheduledForInsert($user));
    }

    public function testScheduleForUpsert()
    {
        $class = $this->dm->getClassMetadata('Documents\ForumUser');
        $user = new ForumUser();
        $user->id = new \MongoId();
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->assertFalse($this->uow->isScheduledForUpsert($user));
        $this->uow->scheduleForUpsert($class, $user);
        $this->assertFalse($this->uow->isScheduledForInsert($user));
        $this->assertTrue($this->uow->isScheduledForUpsert($user));
    }

    public function testGetScheduledDocumentUpserts()
    {
        $class = $this->dm->getClassMetadata('Documents\ForumUser');
        $user = new ForumUser();
        $user->id = new \MongoId();
        $this->assertEmpty($this->uow->getScheduledDocumentUpserts());
        $this->uow->scheduleForUpsert($class, $user);
        $this->assertEquals(array(spl_object_hash($user) => $user), $this->uow->getScheduledDocumentUpserts());
    }

    public function testScheduleForEmbeddedUpsert()
    {
        $class = $this->dm->getClassMetadata('Documents\ForumUser');
        $test = new EmbeddedUpsertDocument();
        $test->id = (string) new \MongoId();
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
        $class = $this->dm->getClassMetadata('Documents\ForumUser');
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


    /* Operational tests */

    public function testSavingSingleDocumentWithIdentityFieldForcesInsert()
    {
        // Setup fake persister and id generator for identity generation
        $pb = $this->getMockPersistenceBuilder();
        $class = $this->dm->getClassMetadata('Documents\ForumUser');
        $userPersister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister('Documents\ForumUser', $userPersister);

        // Test
        $user = new ForumUser();
        $user->username = 'romanb';
        $this->uow->persist($user);

        // Check
        $this->assertEquals(0, count($userPersister->getInserts()));
        $this->assertEquals(0, count($userPersister->getUpdates()));
        $this->assertEquals(0, count($userPersister->getDeletes()));
        $this->assertTrue($this->uow->isInIdentityMap($user));
        // should no longer be scheduled for insert
        $this->assertTrue($this->uow->isScheduledForInsert($user));

        // Now lets check whether a subsequent commit() does anything
        $userPersister->reset();

        // Test
        $this->uow->commit();

        // Check.
        $this->assertEquals(1, count($userPersister->getInserts()));
        $this->assertEquals(0, count($userPersister->getUpdates()));
        $this->assertEquals(0, count($userPersister->getDeletes()));

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
        $class = $this->dm->getClassMetadata('Documents\ForumUser');
        $userPersister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister('Documents\ForumUser', $userPersister);

        // ForumAvatar
        $pb = $this->getMockPersistenceBuilder();
        $class = $this->dm->getClassMetadata('Documents\ForumAvatar');
        $avatarPersister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister('Documents\ForumAvatar', $avatarPersister);

        // Test
        $user = new ForumUser();
        $user->username = 'romanb';
        $avatar = new ForumAvatar();
        $user->avatar = $avatar;
        $this->uow->persist($user); // save cascaded to avatar

        $this->uow->commit();

        $this->assertNotNull($user->id);
        $this->assertNotNull($avatar->id);

        $this->assertEquals(1, count($userPersister->getInserts()));
        $this->assertEquals(0, count($userPersister->getUpdates()));
        $this->assertEquals(0, count($userPersister->getDeletes()));

        $this->assertEquals(1, count($avatarPersister->getInserts()));
        $this->assertEquals(0, count($avatarPersister->getUpdates()));
        $this->assertEquals(0, count($avatarPersister->getDeletes()));
    }

    public function testChangeTrackingNotify()
    {
        $pb = $this->getMockPersistenceBuilder();

        $class = $this->dm->getClassMetadata('Doctrine\ODM\MongoDB\Tests\NotifyChangedDocument');
        $persister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister($class->name, $persister);

        $class = $this->dm->getClassMetadata('Doctrine\ODM\MongoDB\Tests\NotifyChangedRelatedItem');
        $itemPersister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister($class->name, $itemPersister);

        $entity = new NotifyChangedDocument();
        $entity->setId(1);
        $entity->setData('thedata');

        $this->uow->persist($entity);
        $this->uow->commit();

        $this->assertEquals(1, count($persister->getUpserts()));
        $this->assertTrue($this->uow->isInIdentityMap($entity));
        $this->assertFalse($this->uow->isScheduledForDirtyCheck($entity));

        $persister->reset();

        $entity->setData('newdata');
        $entity->setTransient('newtransientvalue');

        $this->assertTrue($this->uow->isScheduledForDirtyCheck($entity));
        $this->assertEquals(array('data' => array('thedata', 'newdata')), $this->uow->getDocumentChangeSet($entity));

        $item = new NotifyChangedRelatedItem();
        $item->setId(1);
        $entity->getItems()->add($item);
        $item->setOwner($entity);

        $this->uow->persist($item);
        $this->uow->commit();

        $this->assertEquals(1, count($itemPersister->getUpserts()));
        $this->assertTrue($this->uow->isInIdentityMap($item));
        $this->assertFalse($this->uow->isScheduledForDirtyCheck($item));

        $persister->reset();
        $itemPersister->reset();

        $entity->getItems()->removeElement($item);
        $item->setOwner(null);

        $this->assertTrue($entity->getItems()->isDirty());

        $this->uow->commit();

        $updates = $itemPersister->getUpdates();

        $this->assertEquals(1, count($updates));
        $this->assertTrue($updates[0] === $item);
    }

    public function testDoubleCommitWithChangeTrackingNotify()
    {
        $pb = $this->getMockPersistenceBuilder();

        $class = $this->dm->getClassMetadata('Doctrine\ODM\MongoDB\Tests\NotifyChangedDocument');
        $persister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister($class->name, $persister);

        $entity = new NotifyChangedDocument();
        $entity->setId(2);
        $this->uow->persist($entity);

        $this->uow->commit($entity);

        // Use a custom error handler that will fail the test if the next commit() call raises a notice error
        set_error_handler(function() {
            restore_error_handler();

            $this->fail('Expected not to get a notice error after committing an entity multiple times using the NOTIFY change tracking policy.');
        }, E_NOTICE);

        $this->uow->commit($entity);

        // Restore previous error handler if no errors have been raised
        restore_error_handler();
    }

    public function testGetDocumentStateWithAssignedIdentity()
    {
        $pb = $this->getMockPersistenceBuilder();
        $class = $this->dm->getClassMetadata("Documents\CmsPhonenumber");
        $persister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister('Documents\CmsPhonenumber', $persister);

        $ph = new \Documents\CmsPhonenumber();
        $ph->phonenumber = '12345';

        $this->assertEquals(UnitOfWork::STATE_NEW, $this->uow->getDocumentState($ph));
        $this->assertTrue($persister->isExistsCalled());

        $persister->reset();

        // if the document is already managed the exists() check should be skipped
        $this->uow->registerManaged($ph, '12345', array());
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->uow->getDocumentState($ph));
        $this->assertFalse($persister->isExistsCalled());
        $ph2 = new \Documents\CmsPhonenumber();
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
        $documentManager->setClassMetadata('Documents\Address', $this->getClassMetadata('Documents\Address', 'MappedSuperclass'));
        $unitOfWork = $this->getUnitOfWork($documentManager);
        $unitOfWork->persist(new \Documents\Address());
    }

    public function testParentAssociations()
    {
        $a = new ParentAssociationTest('a');
        $b = new ParentAssociationTest('b');
        $c = new ParentAssociationTest('c');
        $d = new ParentAssociationTest('c');

        $documentManager = $this->getDocumentManager();
        $unitOfWork = $this->getUnitOfWork($documentManager);
        $unitOfWork->setParentAssociation($b, array('name' => 'b'), $a, 'b');
        $unitOfWork->setParentAssociation($c, array('name' => 'c'), $b, 'b.c');
        $unitOfWork->setParentAssociation($d, array('name' => 'd'), $c, 'b.c.d');

        $this->assertEquals(array(array('name' => 'd'), $c, 'b.c.d'), $unitOfWork->getParentAssociation($d));
    }

    public function testPreUpdateTriggeredWithEmptyChangeset()
    {
        $this->dm->getEventManager()->addEventSubscriber(
            new \Doctrine\ODM\MongoDB\Tests\Mocks\PreUpdateListenerMock()
        );
        $user = new \Documents\ForumUser();
        $user->username = '12345';

        $this->dm->persist($user);
        $this->dm->flush();

        $user->username = '1234';
        $this->dm->persist($user);
        $this->dm->flush();
    }

    public function testNotSaved()
    {
        $test = new \Documents\Functional\NotSaved();
        $test->name = 'test';
        $test->notSaved = 'Jon';
        $this->dm->persist($test);

        $this->uow->computeChangeSets();
        $changeset = $this->uow->getDocumentChangeSet($test);
        $this->assertFalse(isset($changeset['notSaved']));
    }

    /**
     * @dataProvider getScheduleForUpdateWithArraysTests
     */
    public function testScheduleForUpdateWithArrays($origData, $updateData, $shouldInUpdate)
    {
        $pb = $this->getMockPersistenceBuilder();
        $class = $this->dm->getClassMetadata("Doctrine\ODM\MongoDB\Tests\ArrayTest");
        $persister = $this->getMockDocumentPersister($pb, $class);
        $this->uow->setDocumentPersister('Doctrine\ODM\MongoDB\Tests\ArrayTest', $persister);

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
        return array(
            array(
                null,
                array('bar' => 'foo'),
                true
            ),
            array(
                array('foo' => 'bar'),
                null,
                true
            ),
            array(
                array('foo' => 'bar'),
                array('bar' => 'foo'),
                true
            ),
            array(
                array('foo' => 'bar'),
                array('foo' => 'foo'),
                true
            ),
            array(
                array('foo' => 'bar'),
                array('foo' => 'bar'),
                false
            ),
            array(
                array('foo' => 'bar'),
                array('foo' => true),
                true
            ),
            array(
                array('foo' => 'bar'),
                array('foo' => 99),
                true
            ),
            array(
                array('foo' => 99),
                array('foo' => true),
                true
            ),
            array(
                array('foo' => true),
                array('foo' => true),
                false
            ),
        );
    }

    public function testRegisterManagedEmbeddedDocumentWithMappedIdAndNullValue()
    {
        $document = new EmbeddedDocumentWithId();
        $oid = spl_object_hash($document);

        $this->uow->registerManaged($document, null, array());

        $this->assertEquals($oid, $this->uow->getDocumentIdentifier($document));
    }

    public function testRegisterManagedEmbeddedDocumentWithoutMappedId()
    {
        $document = new EmbeddedDocumentWithoutId();
        $oid = spl_object_hash($document);

        $this->uow->registerManaged($document, null, array());

        $this->assertEquals($oid, $this->uow->getDocumentIdentifier($document));
    }

    public function testRegisterManagedEmbeddedDocumentWithMappedIdStrategyNoneAndNullValue()
    {
        $document = new EmbeddedDocumentWithIdStrategyNone();
        $oid = spl_object_hash($document);

        $this->uow->registerManaged($document, null, array());

        $this->assertEquals($oid, $this->uow->getDocumentIdentifier($document));
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
        $address = new \Documents\Address();
        $user = new \Documents\User();
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
        $address = new \Documents\Address();
        $user = new \Documents\User();
        $user->setAddress($address);

        $this->uow->persist($user);

        $this->uow->computeChangeSets();

        $changeSet = $this->uow->getDocumentChangeSet($address);
        $this->assertNotEmpty($changeSet);

        $this->uow->commit();

        $address->setCity('Nashville');

        $this->uow->computeChangeSets();
        $changeSet = $this->uow->getDocumentChangeSet($address);

        $this->assertTrue(isset($changeSet['city']));
        $this->assertEquals('Nashville', $changeSet['city'][1]);
    }

    public function testGetClassNameForAssociation()
    {
        $mapping = array(
            'discriminatorField' => 'type',
            'discriminatorMap' => array(
                'forum_user' => 'Documents\ForumUser',
            ),
            'targetDocument' => 'Documents\User',
        );
        $data = array(
            'type' => 'forum_user',
        );

        $this->assertEquals('Documents\ForumUser', $this->uow->getClassNameForAssociation($mapping, $data));
    }

    public function testGetClassNameForAssociationWithClassMetadataDiscriminatorMap()
    {
        $dm = $this->getMockDocumentManager();
        $uow = new UnitOfWork($dm, $this->getMockEventManager(), $this->getMockHydratorFactory());

        $mapping = array(
            'targetDocument' => 'Documents\User',
        );
        $data = array(
            'type' => 'forum_user',
        );

        $userClassMetadata = new ClassMetadata('Documents\ForumUser');
        $userClassMetadata->discriminatorField = 'type';
        $userClassMetadata->discriminatorMap = array(
            'forum_user' => 'Documents\ForumUser',
        );

        $dm->expects($this->once())
            ->method('getClassMetadata')
            ->with('Documents\User')
            ->will($this->returnValue($userClassMetadata));

        $this->assertEquals('Documents\ForumUser', $uow->getClassNameForAssociation($mapping, $data));
    }

    public function testGetClassNameForAssociationReturnsTargetDocumentWithNullData()
    {
        $mapping = array(
            'targetDocument' => 'Documents\User',
        );
        $this->assertEquals('Documents\User', $this->uow->getClassNameForAssociation($mapping, null));
    }

    public function testRecomputeChangesetForUninitializedProxyDoesNotCreateChangeset()
    {
        $user = new \Documents\ForumUser();
        $user->username = '12345';
        $user->setAvatar(new \Documents\ForumAvatar());

        $this->dm->persist($user);
        $this->dm->flush();

        $id = $user->getId();
        $this->dm->clear();

        $user = $this->dm->find('\Documents\ForumUser', $id);
        $this->assertInstanceOf('\Documents\ForumUser', $user);

        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $user->getAvatar());

        $classMetadata = $this->dm->getClassMetadata('Documents\ForumAvatar');

        $this->uow->recomputeSingleDocumentChangeSet($classMetadata, $user->getAvatar());

        $this->assertEquals(array(), $this->uow->getDocumentChangeSet($user->getAvatar()));
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
     * @return \Doctrine\ODM\MongoDB\Hydrator\HydratorFactory
     */
    private function getMockHydratorFactory()
    {
        return $this->createMock('Doctrine\ODM\MongoDB\Hydrator\HydratorFactory');
    }

    /**
     * Gets mock EventManager instance
     *
     * @return \Doctrine\Common\EventManager
     */
    private function getMockEventManager()
    {
        return $this->createMock('Doctrine\Common\EventManager');
    }

    private function getMockPersistenceBuilder()
    {
        return $this->createMock('Doctrine\ODM\MongoDB\Persisters\PersistenceBuilder');
    }

    private function getMockDocumentManager()
    {
        return $this->createMock('Doctrine\ODM\MongoDB\DocumentManager');
    }

    private function getMockDocumentPersister(PersistenceBuilder $pb, ClassMetadata $class)
    {
        return new DocumentPersisterMock($pb, $this->dm, $this->dm->getEventManager(), $this->uow, $this->dm->getHydratorFactory(), $class);
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
class NotifyChangedDocument implements \Doctrine\Common\NotifyPropertyChanged
{
    private $_listeners = array();

    /** @ODM\Id(type="int_id", strategy="none") */
    private $id;

    /** @ODM\Field(type="string") */
    private $data;

    /** @ODM\ReferenceMany(targetDocument="NotifyChangedRelatedItem") */
    private $items;

    private $transient; // not persisted

    public function  __construct()
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
        if ($data != $this->data) {
            $this->_onPropertyChanged('data', $this->data, $data);
            $this->data = $data;
        }
    }

    public function getItems()
    {
        return $this->items;
    }

    public function setTransient($value)
    {
        if ($value != $this->transient) {
            $this->_onPropertyChanged('transient', $this->transient, $value);
            $this->transient = $value;
        }
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

    /** @ODM\ReferenceOne(targetDocument="NotifyChangedDocument") */
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

    /** @ODM\EmbedOne(targetDocument="EmbeddedDocumentWithId") */
    public $embedded;
}
