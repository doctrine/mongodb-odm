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

        $pCollection = new PersistentCollection($collection);
        $pCollection->slice($start, $limit);
    }

    /**
     * @return Doctrine\Common\Collections\Collection
     */
    protected function getCollectionMock()
    {
        return $this->getMock('Doctrine\Common\Collections\Collection', get_class_methods('Doctrine\Common\Collections\Collection'), array(), '', false, false);
    }
}