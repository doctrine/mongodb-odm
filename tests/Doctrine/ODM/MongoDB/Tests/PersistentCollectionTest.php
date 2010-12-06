<?php

namespace Doctrine\ODM\MongoDB\Tests;

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
        $pCollection = new PersistentCollection($collection, $dm, $uow, '$');
        $pCollection->slice($start, $limit);
    }


    /**
     * @return Doctrine\ODM\MongoDB\DocumentManager
     */
    private function getMockDocumentManager()
    {
        return $this->getMock('Doctrine\ODM\MongoDB\DocumentManager', array(), array(), '', false, false);
    }

    /**
     * @return Doctrine\ODM\MongoDB\UnitOfWork
     */
    private function getMockUnitOfWork()
    {
        return $this->getMock('Doctrine\ODM\MongoDB\UnitOfWork', array(), array(), '', false, false);
    }

    /**
     * @return Doctrine\Common\Collections\Collection
     */
    private function getMockCollection()
    {
        return $this->getMock('Doctrine\Common\Collections\Collection', get_class_methods('Doctrine\Common\Collections\Collection'), array(), '', false, false);
    }
}