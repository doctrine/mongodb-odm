<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Documents\User;
use function serialize;
use function unserialize;

class PersistentCollectionTest extends BaseTest
{
    public function testSlice()
    {
        list ($start, $limit) = [0, 25];
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

        $unserialized->setOwner($owner, ['targetDocument' => '\stdClass']);
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

        $unserialized->setOwner(new User(), $this->dm->getClassMetadata(User::class)->getFieldMapping('phonebooks'));
        $unserialized->setDocumentManager($this->dm);
        $this->assertInstanceOf(ClassMetadata::class, $unserialized->getTypeClass());
    }

    /**
     * @param array $expected
     * @param array $snapshot
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

        return [
            'sameItems' => [
                [],
                [$one],
                function ($collection) {
                },
            ],
            'added' => [
                [],
                [$one],
                function ($collection) use ($two) {
                    $collection->add($two);
                },
            ],
            'removed' => [
                [$one],
                [$one, $two],
                function ($collection) use ($one) {
                    $collection->removeElement($one);
                },
            ],
            'replaced' => [
                [$one],
                [$one],
                function ($collection) use ($one, $two) {
                    $collection->removeElement($one);
                    $collection->add($two);
                },
            ],
            'removed2' => [
                [$two],
                [$one, $two],
                function ($collection) use ($two) {
                    $collection->removeElement($two);
                },
            ],
            'orderChanged' => [
                [],
                [$one, $two],
                function ($collection) use ($one, $two) {
                    $collection->removeElement($one);
                    $collection->removeElement($two);
                    $collection->add($two);
                    $collection->add($one);
                },
            ],
        ];
    }

    /**
     * @param array $expected
     * @param array $snapshot
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

        return [
            'sameItems' => [
                [],
                [$one],
                function ($collection) {
                },
            ],
            'added' => [
                [$two],
                [$one],
                function ($collection) use ($two) {
                    $collection->add($two);
                },
            ],
            'replaced' => [
                [$two],
                [$one],
                function ($collection) use ($one, $two) {
                    $collection->removeElement($one);
                    $collection->add($two);
                },
            ],
            'orderChanged' => [
                [],
                [$one, $two],
                function ($collection) use ($one, $two) {
                    $collection->removeElement($one);
                    $collection->removeElement($two);
                    $collection->add($two);
                    $collection->add($one);
                },
            ],
        ];
    }

    public function testOffsetExistsIsForwarded()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->once())->method('offsetExists')->willReturn(false);
        $pcoll = new PersistentCollection($collection, $this->getMockDocumentManager(), $this->getMockUnitOfWork());
        $this->assertArrayNotHasKey(0, $pcoll);
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
     * @return DocumentManager
     */
    private function getMockDocumentManager()
    {
        return $this->createMock(DocumentManager::class);
    }

    /**
     * @return UnitOfWork
     */
    private function getMockUnitOfWork()
    {
        return $this->createMock(UnitOfWork::class);
    }

    /**
     * @return Collection
     */
    private function getMockCollection()
    {
        return $this->createMock(Collection::class);
    }
}
