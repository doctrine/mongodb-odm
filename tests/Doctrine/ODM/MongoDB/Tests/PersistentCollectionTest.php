<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Closure;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\PersistentCollection;
use Documents\User;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;

use function assert;
use function serialize;
use function unserialize;

class PersistentCollectionTest extends BaseTest
{
    public function testSlice()
    {
        [$start, $limit] = [0, 25];
        $collection      = $this->getMockCollection();
        $collection->expects($this->once())
            ->method('slice')
            ->with($start, $limit)
            ->will($this->returnValue(true));
        $pCollection = new PersistentCollection($collection, $this->dm, $this->uow);
        $pCollection->slice($start, $limit);
    }

    public function testExceptionForGetTypeClassWithoutDocumentManager()
    {
        $collection = new PersistentCollection(new ArrayCollection(), $this->dm, $this->uow);
        $owner      = new stdClass();

        $serialized   = serialize($collection);
        $unserialized = unserialize($serialized);
        assert($unserialized instanceof PersistentCollection);

        $unserialized->setOwner($owner, ['targetDocument' => '\stdClass']);
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage(
            'No DocumentManager is associated with this PersistentCollection, ' .
            'please set one using setDocumentManager method.'
        );
        $unserialized->getTypeClass();
    }

    public function testExceptionForGetTypeClassWithoutMapping()
    {
        $collection = new PersistentCollection(new ArrayCollection(), $this->dm, $this->uow);

        $serialized   = serialize($collection);
        $unserialized = unserialize($serialized);
        assert($unserialized instanceof PersistentCollection);

        $unserialized->setDocumentManager($this->dm);
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage(
            'No mapping is associated with this PersistentCollection, ' .
            'please set one using setOwner method.'
        );
        $unserialized->getTypeClass();
    }

    public function testGetTypeClassWorksAfterUnserialization()
    {
        $collection = new PersistentCollection(new ArrayCollection(), $this->dm, $this->uow);

        $serialized   = serialize($collection);
        $unserialized = unserialize($serialized);
        assert($unserialized instanceof PersistentCollection);

        $unserialized->setOwner(new User(), $this->dm->getClassMetadata(User::class)->getFieldMapping('phonebooks'));
        $unserialized->setDocumentManager($this->dm);
        $this->assertInstanceOf(ClassMetadata::class, $unserialized->getTypeClass());
    }

    public function testMongoDataIsPreservedDuringSerialization()
    {
        $mongoData = [
            [
                '$ref' => 'group',
                '$id' => new ObjectId(),
            ],
            [
                '$ref' => 'group',
                '$id' => new ObjectId(),
            ],
        ];

        $collection = new PersistentCollection(new ArrayCollection(), $this->dm, $this->uow);
        $collection->setMongoData($mongoData);

        $serialized   = serialize($collection);
        $unserialized = unserialize($serialized);
        assert($unserialized instanceof PersistentCollection);

        $unserialized->setOwner(new User(), $this->dm->getClassMetadata(User::class)->getFieldMapping('groups'));
        $unserialized->setDocumentManager($this->dm);

        $this->assertCount(2, $unserialized->getMongoData());
    }

    public function testSnapshotIsPreservedDuringSerialization()
    {
        $collection = new PersistentCollection(new ArrayCollection(), $this->dm, $this->uow);
        $collection->add(new stdClass());
        $collection->takeSnapshot();

        $this->assertCount(1, $collection->getSnapshot());
        $this->assertFalse($collection->isDirty());
        $this->assertCount(1, $collection->unwrap());

        $serialized   = serialize($collection);
        $unserialized = unserialize($serialized);
        assert($unserialized instanceof PersistentCollection);

        $unserialized->setOwner(new User(), $this->dm->getClassMetadata(User::class)->getFieldMapping('groups'));
        $unserialized->setDocumentManager($this->dm);

        $this->assertCount(1, $unserialized->getSnapshot());
        $this->assertFalse($unserialized->isDirty());
        $this->assertCount(1, $unserialized->unwrap());
    }

    /**
     * @param array $expected
     * @param array $snapshot
     *
     * @dataProvider dataGetDeletedDocuments
     */
    public function testGetDeletedDocuments($expected, $snapshot, Closure $callback)
    {
        $collection = new PersistentCollection(new ArrayCollection(), $this->dm, $this->uow);

        foreach ($snapshot as $item) {
            $collection->add($item);
        }

        $collection->takeSnapshot();
        $callback($collection);

        $this->assertSame($expected, $collection->getDeletedDocuments());
    }

    public static function dataGetDeletedDocuments()
    {
        $one = new stdClass();
        $two = new stdClass();

        return [
            'sameItems' => [
                [],
                [$one],
                static function ($collection) {
                },
            ],
            'added' => [
                [],
                [$one],
                static function ($collection) use ($two) {
                    $collection->add($two);
                },
            ],
            'removed' => [
                [$one],
                [$one, $two],
                static function ($collection) use ($one) {
                    $collection->removeElement($one);
                },
            ],
            'replaced' => [
                [$one],
                [$one],
                static function ($collection) use ($one, $two) {
                    $collection->removeElement($one);
                    $collection->add($two);
                },
            ],
            'removed2' => [
                [$two],
                [$one, $two],
                static function ($collection) use ($two) {
                    $collection->removeElement($two);
                },
            ],
            'orderChanged' => [
                [],
                [$one, $two],
                static function ($collection) use ($one, $two) {
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
    public function testGetInsertedDocuments($expected, $snapshot, Closure $callback)
    {
        $collection = new PersistentCollection(new ArrayCollection(), $this->dm, $this->uow);

        foreach ($snapshot as $item) {
            $collection->add($item);
        }

        $collection->takeSnapshot();
        $callback($collection);

        $this->assertSame($expected, $collection->getInsertedDocuments());
    }

    public static function dataGetInsertedDocuments()
    {
        $one = new stdClass();
        $two = new stdClass();

        return [
            'sameItems' => [
                [],
                [$one],
                static function ($collection) {
                },
            ],
            'added' => [
                [$two],
                [$one],
                static function ($collection) use ($two) {
                    $collection->add($two);
                },
            ],
            'replaced' => [
                [$two],
                [$one],
                static function ($collection) use ($one, $two) {
                    $collection->removeElement($one);
                    $collection->add($two);
                },
            ],
            'orderChanged' => [
                [],
                [$one, $two],
                static function ($collection) use ($one, $two) {
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
        $pcoll = new PersistentCollection($collection, $this->dm, $this->uow);
        $this->assertArrayNotHasKey(0, $pcoll);
    }

    public function testOffsetGetIsForwarded()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->once())->method('offsetGet')->willReturn(2);
        $pcoll = new PersistentCollection($collection, $this->dm, $this->uow);
        $this->assertSame(2, $pcoll[0]);
    }

    public function testOffsetUnsetIsForwarded()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->once())->method('offsetUnset');
        $pcoll = new PersistentCollection($collection, $this->dm, $this->uow);
        unset($pcoll[0]);
        $this->assertTrue($pcoll->isDirty());
    }

    public function testRemoveIsForwarded()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->once())->method('remove')->willReturn(2);
        $pcoll = new PersistentCollection($collection, $this->dm, $this->uow);
        $pcoll->remove(0);
        $this->assertTrue($pcoll->isDirty());
    }

    public function testOffsetSetIsForwarded()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->exactly(2))->method('offsetSet');
        $pcoll    = new PersistentCollection($collection, $this->dm, $this->uow);
        $pcoll[]  = 1;
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
        $pcoll = new PersistentCollection($collection, $this->dm, $this->uow);
        $pcoll->setInitialized(true);
        $this->assertTrue($pcoll->isEmpty());
    }

    public function testIsEmptyUsesCountWhenCollectionIsNotInitialized()
    {
        $collection = $this->getMockCollection();
        $collection->expects($this->never())->method('isEmpty');
        $collection->expects($this->once())->method('count')->willReturn(0);
        $pcoll = new PersistentCollection($collection, $this->dm, $this->uow);
        $pcoll->setInitialized(false);
        $this->assertTrue($pcoll->isEmpty());
    }

    /**
     * @return Collection&MockObject
     */
    private function getMockCollection()
    {
        return $this->createMock(Collection::class);
    }
}
