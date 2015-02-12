<?php

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\ODM\MongoDB\Types\Type;

class DateTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertToDatabaseValue()
    {
        $type = Type::getType('date');

        $date = \DateTime::createFromFormat('U.u', '1423743340.626000'); // "seconds.microseconds"

        $this->assertNull($type->convertToDatabaseValue(null), 'null is not converted');
        $this->assertEquals(1423743340, $type->convertToDatabaseValue($time)->sec);
        $this->assertEquals(626000, $type->convertToDatabaseValue($time)->usec);
    }

    public function testConvertToPHPValue()
    {
        $type = Type::getType('date');

        $date = new \MongoDate(1423743340, 626000);

        $this->assertNull($type->convertToPHPValue(null), 'null is not converted');
        $this->assertEquals('1423743340.626000', $type->convertToPHPValue($date)->format('U.u'));
    }
}
