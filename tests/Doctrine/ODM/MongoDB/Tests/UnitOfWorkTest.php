<?php

namespace Doctrine\ODM\MongoDB\Tests;

require_once __DIR__ . '/../../../../TestInit.php';

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentManagerMock;
use Doctrine\ODM\MongoDB\Tests\Mocks\MongoMock;
use Doctrine\ODM\MongoDB\Tests\Mocks\UnitOfWorkMock;
use Doctrine\ODM\MongoDB\Tests\Mocks\DocumentPersisterMock;
use Documents\ForumUser;
use Documents\ForumAvatar;


class UnitOfWorkTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_dmMock = DocumentManagerMock::create(new MongoMock());
        $this->_unitOfWork = $this->_dmMock->getUnitOfWork();
    }
    
    protected function tearDown() {
    }
    
    public function testRegisterRemovedOnNewEntityIsIgnored()
    {
        $user = new ForumUser();
        $user->username = 'romanb';
        $this->assertFalse($this->_unitOfWork->isScheduledForDelete($user));
        $this->_unitOfWork->scheduleForDelete($user);
        $this->assertFalse($this->_unitOfWork->isScheduledForDelete($user));        
    }
    
    
    /* Operational tests */
    
    public function testSavingSingleEntityWithIdentityColumnForcesInsert()
    {
        // Setup fake persister and id generator for identity generation
        $userPersister = new DocumentPersisterMock($this->_dmMock, $this->_dmMock->getClassMetadata('Documents\ForumUser'));
        $this->_unitOfWork->setDocumentPersister('Documents\ForumUser', $userPersister);

        // Test
        $user = new ForumUser();
        $user->username = 'romanb';
        $this->_unitOfWork->persist($user);
    
        // Check
        $this->assertEquals(0, count($userPersister->getInserts()));
        $this->assertEquals(0, count($userPersister->getUpdates()));
        $this->assertEquals(0, count($userPersister->getDeletes()));   
        $this->assertFalse($this->_unitOfWork->isInIdentityMap($user));
        // should no longer be scheduled for insert
        $this->assertTrue($this->_unitOfWork->isScheduledForInsert($user));
    
        // Now lets check whether a subsequent commit() does anything
        $userPersister->reset();
    
        // Test
        $this->_unitOfWork->commit();
    
        // Check.
        $this->assertEquals(1, count($userPersister->getInserts()));
        $this->assertEquals(0, count($userPersister->getUpdates()));
        $this->assertEquals(0, count($userPersister->getDeletes()));
    
        // should have an id
        $this->assertTrue(is_numeric($user->id));
    }
    
    /**
     * Tests a scenario where a save() operation is cascaded from a ForumUser
     * to its associated ForumAvatar, both entities using IDENTITY id generation.
     */
    public function testCascadedIdentityColumnInsert()
    {
        // Setup fake persister and id generator for identity generation
        //ForumUser
        $userPersister = new DocumentPersisterMock($this->_dmMock, $this->_dmMock->getClassMetadata("Documents\ForumUser"));
        $this->_unitOfWork->setDocumentPersister('Documents\ForumUser', $userPersister);
        // ForumAvatar
        $avatarPersister = new DocumentPersisterMock($this->_dmMock, $this->_dmMock->getClassMetadata("Documents\ForumAvatar"));
        $this->_unitOfWork->setDocumentPersister('Documents\ForumAvatar', $avatarPersister);

        // Test
        $user = new ForumUser();
        $user->username = 'romanb';
        $avatar = new ForumAvatar();
        $user->avatar = $avatar;
        $this->_unitOfWork->persist($user); // save cascaded to avatar
    
        $this->_unitOfWork->commit();
    
        $this->assertTrue(is_numeric($user->id));
        $this->assertTrue(is_numeric($avatar->id));
    
        $this->assertEquals(1, count($userPersister->getInserts()));
        $this->assertEquals(0, count($userPersister->getUpdates()));
        $this->assertEquals(0, count($userPersister->getDeletes()));
    
        $this->assertEquals(1, count($avatarPersister->getInserts()));
        $this->assertEquals(0, count($avatarPersister->getUpdates()));
        $this->assertEquals(0, count($avatarPersister->getDeletes()));
    }
    
    public function testChangeTrackingNotify()
    {
        $persister = new DocumentPersisterMock($this->_dmMock, $this->_dmMock->getClassMetadata("Doctrine\ODM\MongoDB\Tests\NotifyChangedDocument"));
        $this->_unitOfWork->setDocumentPersister('Doctrine\ODM\MongoDB\Tests\NotifyChangedDocument', $persister);
        $itemPersister = new DocumentPersisterMock($this->_dmMock, $this->_dmMock->getClassMetadata("Doctrine\ODM\MongoDB\Tests\NotifyChangedRelatedItem"));
        $this->_unitOfWork->setDocumentPersister('Doctrine\ODM\MongoDB\Tests\NotifyChangedRelatedItem', $itemPersister);
    
        $entity = new NotifyChangedDocument;
        $entity->setData('thedata');
        $this->_unitOfWork->persist($entity);
    
        $this->_unitOfWork->commit();
        $this->assertEquals(1, count($persister->getInserts()));
        $persister->reset();
    
        $this->assertTrue($this->_unitOfWork->isInIdentityMap($entity));
    
        $entity->setData('newdata');
        $entity->setTransient('newtransientvalue');
    
        $this->assertTrue($this->_unitOfWork->isScheduledForDirtyCheck($entity));
    
        $this->assertEquals(array('data' => array('thedata', 'newdata')), $this->_unitOfWork->getDocumentChangeSet($entity));
    
        $item = new NotifyChangedRelatedItem();
        $entity->getItems()->add($item);
        $item->setOwner($entity);
        $this->_unitOfWork->persist($item);
    
        $this->_unitOfWork->commit();
        $this->assertEquals(1, count($itemPersister->getInserts()));
        $persister->reset();
        $itemPersister->reset();
    
    
        $entity->getItems()->removeElement($item);
        $item->setOwner(null);
        $this->assertTrue($entity->getItems()->isDirty());
        $this->_unitOfWork->commit();
        $updates = $itemPersister->getUpdates();
        $this->assertEquals(1, count($updates));
        $this->assertTrue($updates[0] === $item);
    }
    
    public function testGetDocumentStateWithAssignedIdentity()
    {
        $persister = new DocumentPersisterMock($this->_dmMock, $this->_dmMock->getClassMetadata("Documents\CmsPhonenumber"));
        $this->_unitOfWork->setDocumentPersister('Documents\CmsPhonenumber', $persister);
    
        $ph = new \Documents\CmsPhonenumber();
        $ph->phonenumber = '12345';
    
        $this->assertEquals(UnitOfWork::STATE_NEW, $this->_unitOfWork->getDocumentState($ph));
        $this->assertTrue($persister->isExistsCalled());
    
        $persister->reset();
    
        // if the document is already managed the exists() check should be skipped
        $this->_unitOfWork->registerManaged($ph, '12345', array());
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $this->_unitOfWork->getDocumentState($ph));
        $this->assertFalse($persister->isExistsCalled());
        $ph2 = new \Documents\CmsPhonenumber();
        $ph2->phonenumber = '12345';
        $this->assertEquals(UnitOfWork::STATE_DETACHED, $this->_unitOfWork->getDocumentState($ph2));
        $this->assertFalse($persister->isExistsCalled());
    }
    
    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testThrowsOnPersistOfEmbeddedDocument()
    {
        $documentManager = $this->getDocumentManager();
        $documentManager->setClassMetadata('Documents\Address', $this->getClassMetadata('Documents\Address', 'EmbeddedDocument'));
        $unitOfWork = $this->getUnitOfWork($documentManager);
        $unitOfWork->persist(new \Documents\Address());
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

    protected function getDocumentManager()
    {
        return new \Stubs\DocumentManager();
    }

    protected function getUnitOfWork(DocumentManager $dm)
    {
        return new UnitOfWork($dm);
    }

    protected function getClassMetadata($class, $flag)
    {
        $classMetadata = new ClassMetadata($class);
        $classMetadata->{'is' . ucfirst($flag)} = true;
        return $classMetadata;
    }
}

/**
 * @Document
 */
class NotifyChangedDocument implements \Doctrine\Common\NotifyPropertyChanged
{
    private $_listeners = array();
    /**
     * @Id
     */
    private $id;
    /**
     * @String
     */
    private $data;

    private $transient; // not persisted

    /** @ReferenceMany(targetDocument="NotifyChangedRelatedItem") */
    private $items;

    public function  __construct() {
        $this->items = new \Doctrine\Common\Collections\ArrayCollection;
    }

    public function getId() {
        return $this->id;
    }

    public function getItems() {
        return $this->items;
    }

    public function setTransient($value) {
        if ($value != $this->transient) {
            $this->_onPropertyChanged('transient', $this->transient, $value);
            $this->transient = $value;
        }
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        if ($data != $this->data) {
            $this->_onPropertyChanged('data', $this->data, $data);
            $this->data = $data;
        }
    }

    public function addPropertyChangedListener(\Doctrine\Common\PropertyChangedListener $listener)
    {
        $this->_listeners[] = $listener;
    }

    protected function _onPropertyChanged($propName, $oldValue, $newValue) {
        if ($this->_listeners) {
            foreach ($this->_listeners as $listener) {
                $listener->propertyChanged($this, $propName, $oldValue, $newValue);
            }
        }
    }
}

/** @Document */
class NotifyChangedRelatedItem
{
    /**
     * @Id
     */
    private $id;

    /** @ReferenceOne(targetDocument="NotifyChangedDocument") */
    private $owner;

    public function getId() {
        return $this->id;
    }

    public function getOwner() {
        return $this->owner;
    }

    public function setOwner($owner) {
        $this->owner = $owner;
    }
}