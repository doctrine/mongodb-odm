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
        $pCollection = new PersistentCollection($collection, $dm, $uow);
        $pCollection->slice($start, $limit);
    }

    /**
     * @return Doctrine\ODM\MongoDB\DocumentManager
     */
    private function getMockDocumentManager()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\DocumentManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return Doctrine\ODM\MongoDB\UnitOfWork
     */
    private function getMockUnitOfWork()
    {
        return $this->getMockBuilder('Doctrine\ODM\MongoDB\UnitOfWork')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return Doctrine\Common\Collections\Collection
     */
    private function getMockCollection()
    {
        return $this->getMock('Doctrine\Common\Collections\Collection');
    }

    public function testIsDirtyAfterInitialize()
    {
        $collection = $this->getMockCollection();
        $dm         = $this->getMockDocumentManager();
        $uow        = $this->getMockUnitOfWork();
        $pCollection = new PersistentCollection($collection, $dm, $uow);
        $pCollection->setOwner($owner = new \stdClass, $mapping = [
            'isOwningSide'   => false              ,
            'targetDocument' => 'Not\A\Real\Class' ,
            ]);
        $pCollection->setInitialized(false);
        $this->assertFalse($pCollection->isInitialized());
        $this->assertFalse($pCollection->isDirty());
        $document = new \stdClass;
        $collection->expects($this->any())
            ->method('toArray')
            ->will($this->returnValue($document))
            ;
        $pCollection->add($document);
        $this->assertTrue($pCollection->isDirty());
        $pCollection->initialize();
        $this->assertTrue($pCollection->isDirty());
    }

}
