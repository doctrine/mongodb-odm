<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class PersistentCollectionTest extends \PHPUnit_Framework_TestCase
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
