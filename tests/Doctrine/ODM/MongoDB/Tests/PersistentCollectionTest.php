<?php

namespace Doctrine\ODM\MongoDB\Tests;

require_once __DIR__ . '/../../../../TestInit.php';

use Doctrine\ODM\MongoDB\PersistentCollection;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class PersistentCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testSlice()
    {
        list ($start, $limit) = array(0, 25);
        $collection = $this->getCollectionMock();
        $collection->expects($this->once())
            ->method('slice')
            ->with($start, $limit)
            ->will($this->returnValue(true));

        $configuration = $this->getConfigurationMock();
        $configuration->expects($this->once())
            ->method('getMongoCmd')
            ->will($this->returnValue('$'));
        $dm = $this->getDocumentManagerMock();
        $pCollection = new PersistentCollection($collection, $dm, $configuration);
        $pCollection->slice($start, $limit);
    }


    /**
     * @return Doctrine\ODM\MongoDB\DocumentManager
     */
    protected function getDocumentManagerMock()
    {
        return $this->getMock('Doctrine\ODM\MongoDB\DocumentManager', array(), array(), '', false, false);
    }

    /**
     * @return Doctrine\ODM\MongoDB\Configuration
     */
    protected function getConfigurationMock()
    {
        return $this->getMock('Doctrine\ODM\MongoDB\Configuration', array(), array(), '', false, false);
    }

    /**
     * @return Doctrine\Common\Collections\Collection
     */
    protected function getCollectionMock()
    {
        return $this->getMock('Doctrine\Common\Collections\Collection', get_class_methods('Doctrine\Common\Collections\Collection'), array(), '', false, false);
    }
}