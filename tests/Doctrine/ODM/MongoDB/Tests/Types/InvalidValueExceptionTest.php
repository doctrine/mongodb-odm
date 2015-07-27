<?php

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Types\Type;

class InvalidValueExceptionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Collection type requires value of type array or null, Doctrine\Common\Collections\ArrayCollection given
     */
    public function testCollectionDoesntAcceptObject()
    {
        $t = Type::getType('collection');
        $t->convertToDatabaseValue(new ArrayCollection());
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Collection type requires value of type array or null, scalar given
     */
    public function testCollectionDoesntAcceptScalar()
    {
        $t = Type::getType('collection');
        $t->convertToDatabaseValue(true);
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Hash type requires value of type array or null, Doctrine\Common\Collections\ArrayCollection given
     */
    public function testHashDoesntAcceptObject()
    {
        $t = Type::getType('hash');
        $t->convertToDatabaseValue(new ArrayCollection());
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\MongoDBException
     * @expectedExceptionMessage Hash type requires value of type array or null, scalar given
     */
    public function testHashDoesntAcceptScalar()
    {
        $t = Type::getType('hash');
        $t->convertToDatabaseValue(true);
    }
}
