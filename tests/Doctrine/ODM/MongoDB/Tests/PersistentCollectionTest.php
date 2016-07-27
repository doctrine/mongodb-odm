<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection;

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
        $this->assertInstanceOf(ClassMetadata::class, $unserialized->getTypeClass());
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
                array($one),
                array($one, $two),
                function ($collection) use ($one) { $collection->removeElement($one); }
            ),
            'replaced' => array(
                array($one),
                array($one),
                function ($collection) use ($one, $two) { $collection->removeElement($one); $collection->add($two); }
            ),
            'removed2' => array(
                array($two),
                array($one, $two),
                function ($collection) use ($two) { $collection->removeElement($two); }
            ),
            'orderChanged' => array(
                array(),
                array($one, $two),
                function ($collection) use ($one, $two) { $collection->removeElement($one); $collection->removeElement($two); $collection->add($two); $collection->add($one); }
            ),
        );
    }

    /**
     * @param array $expected
     * @param array $snapshot
     * @param \Closure $callback
     *
     * @dataProvider dataGetInsertedDocuments
     */
    public function testGetInsertedDocuments($expected, $snapshot, \Closure $callback)
    {
        $collection = new PersistentCollection(new ArrayCollection(), $this->getMockDocumentManager(), $this->getMockUnitOfWork());

        foreach ($snapshot as $item) {
            $collection->add($item);
        }
        $collection->takeSnapshot();
        $callback($collection);

        $this->assertSame($expected, $collection->getInsertedDocuments());
    }

    public static function dataGetInsertedDocuments()
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
                array($two),
                array($one),
                function ($collection) use ($two) { $collection->add($two); }
            ),
            'replaced' => array(
                array($two),
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

    public function testOffsetExistsIsForwarded()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->once())->method('offsetExists')->willReturn(false);
        $pcoll = new PersistentCollection($collection, $this->getMockDocumentManager(), $this->getMockUnitOfWork());
        $this->assertFalse(isset($pcoll[0]));
    }

    public function testOffsetGetIsForwarded()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->once())->method('offsetGet')->willReturn(2);
        $pcoll = new PersistentCollection($collection, $this->getMockDocumentManager(), $this->getMockUnitOfWork());
        $this->assertSame(2, $pcoll[0]);
    }

    public function testOffsetUnsetIsForwarded()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->once())->method('offsetUnset');
        $pcoll = new PersistentCollection($collection, $this->getMockDocumentManager(), $this->getMockUnitOfWork());
        unset($pcoll[0]);
        $this->assertTrue($pcoll->isDirty());
    }

    public function testRemoveIsForwarded()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->once())->method('remove')->willReturn(2);
        $pcoll = new PersistentCollection($collection, $this->getMockDocumentManager(), $this->getMockUnitOfWork());
        $pcoll->remove(0);
        $this->assertTrue($pcoll->isDirty());
    }

    public function testOffsetSetIsForwarded()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->exactly(2))->method('offsetSet');
        $pcoll = new PersistentCollection($collection, $this->getMockDocumentManager(), $this->getMockUnitOfWork());
        $pcoll[] = 1;
        $pcoll[1] = 2;
        $collection->expects($this->once())->method('add');
        $pcoll->add(3);
        $collection->expects($this->once())->method('set');
        $pcoll->set(3, 4);
    }

    public function testIsEmptyIsForwardedWhenCollectionIsInitialized()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->once())->method('isEmpty')->willReturn(true);
        $pcoll = new PersistentCollection($collection, $this->getMockDocumentManager(), $this->getMockUnitOfWork());
        $pcoll->setInitialized(true);
        $this->assertTrue($pcoll->isEmpty());
    }

    public function testIsEmptyUsesCountWhenCollectionIsNotInitialized()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->never())->method('isEmpty');
        $collection->expects($this->once())->method('count')->willReturn(0);
        $pcoll = new PersistentCollection($collection, $this->getMockDocumentManager(), $this->getMockUnitOfWork());
        $pcoll->setInitialized(false);
        $this->assertTrue($pcoll->isEmpty());
    }

    /**
     * @return \Doctrine\ODM\MongoDB\DocumentManager
     */
    private function getMockDocumentManager()
    {
        return $this->createMock('Doctrine\ODM\MongoDB\DocumentManager');
    }

    /**
     * @return \Doctrine\ODM\MongoDB\UnitOfWork
     */
    private function getMockUnitOfWork()
    {
        return $this->createMock('Doctrine\ODM\MongoDB\UnitOfWork');
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    private function getMockCollection()
    {
        return $this->createMock('Doctrine\Common\Collections\Collection');
    }
}
