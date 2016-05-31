<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class PersistentCollectionTest extends BaseTest
{
    public function testSlice()
    {
        list ($start, $limit) = array(0, 25);
        $collection = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('slice')
            ->with($start, $limit)
            ->will($this->returnValue(true));
        $dm = $this->getMockDocumentManager();
        $uow = $this->getMockUnitOfWork();
        $pCollection = new PersistentCollection($collection, $dm, $uow);
        $pCollection->slice($start, $limit);
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage No DocumentManager is associated with this PersistentCollection, please set one using setDocumentManager method.
     */
    public function testExceptionForGetTypeClassWithoutDocumentManager()
    {
        $collection = new PersistentCollection(new ArrayCollection(), $this->getMockDocumentManager(), $this->getMockUnitOfWork());
        $owner = new \stdClass();

        $serialized = serialize($collection);
        /** @var PersistentCollection $unserialized */
        $unserialized = unserialize($serialized);

        $unserialized->setOwner($owner, array('targetDocument' => '\stdClass'));
        $unserialized->getTypeClass();
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage No mapping is associated with this PersistentCollection, please set one using setOwner method.
     */
    public function testExceptionForGetTypeClassWithoutMapping()
    {
        $collection = new PersistentCollection(new ArrayCollection(), $this->getMockDocumentManager(), $this->getMockUnitOfWork());

        $serialized = serialize($collection);
        /** @var PersistentCollection $unserialized */
        $unserialized = unserialize($serialized);

        $unserialized->setDocumentManager($this->dm);
        $unserialized->getTypeClass();
    }

    public function testGetTypeClassWorksAfterUnserialization()
    {
        $collection = new PersistentCollection(new ArrayCollection(), $this->dm, $this->uow);

        $serialized = serialize($collection);
        /** @var PersistentCollection $unserialized */
        $unserialized = unserialize($serialized);

        $unserialized->setOwner(new \Documents\User(), $this->dm->getClassMetadata('Documents\\User')->getFieldMapping('phonebooks'));
        $unserialized->setDocumentManager($this->dm);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Mapping\ClassMetadata', $unserialized->getTypeClass());
    }

    /**
     * @param array $expected
     * @param array $snapshot
     * @param \Closure $callback
     *
     * @dataProvider dataGetDeletedDocuments
     */
    public function testGetDeletedDocuments($expected, $snapshot, \Closure $callback)
    {
        $collection = new PersistentCollection(new ArrayCollection(), $this->getMockDocumentManager(), $this->getMockUnitOfWork());

        foreach ($snapshot as $item) {
            $collection->add($item);
        }
        $collection->takeSnapshot();
        $callback($collection);

        $this->assertSame($expected, $collection->getDeletedDocuments());
    }

    public static function dataGetDeletedDocuments()
    {
        $one = new \stdClass();
        $two = new \stdClass();

        return array(
            'sameItems' => array(
                array(),
                array($one),
                function ($collection) {}
            ),
            'added' => array(
                array(),
                array($one),
                function ($collection) use ($two) { $collection->add($two); }
            ),
            'removed' => array(
                array(0 => $one),
                array($one, $two),
                function ($collection) use ($one) { $collection->removeElement($one); }
            ),
            'replaced' => array(
                array(0 => $one),
                array($one),
                function ($collection) use ($one, $two) { $collection->removeElement($one); $collection->add($two); }
            ),
            'orderChanged' => array(
                array(),
                array($one, $two),
                function ($collection) use ($one, $two) { $collection->removeElement($one); $collection->removeElement($two); $collection->add($two); $collection->add($one); }
            ),
        );
    }

    /**
     * @return \Doctrine\ODM\MongoDB\DocumentManager
     */
    private function getMockDocumentManager()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \Doctrine\ODM\MongoDB\UnitOfWork
     */
    private function getMockUnitOfWork()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    private function getMockCollection()
    {
        return $this->getMock('Doctrine\Common\Collections\Collection');
    }
}
