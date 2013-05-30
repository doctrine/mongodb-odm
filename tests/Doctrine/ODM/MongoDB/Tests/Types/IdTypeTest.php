<?php

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\ODM\MongoDB\Types\Type;

class IdTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testConvertToDatabaseValue()
    {
        $mongoId = new \MongoId();
        $type = Type::getType('id');

        $this->assertNull($type->convertToDatabaseValue(null), 'null is not converted');
        $this->assertSame($mongoId, $type->convertToDatabaseValue($mongoId), 'MongoId objects are not converted');
        $this->assertEquals($mongoId, $type->convertToDatabaseValue((string) $mongoId), 'ObjectId strings are converted to MongoId objects');
    }

    /**
     * @dataProvider provideInvalidMongoIdConstructorArguments
     */
    public function testConvertToDatabaseValueShouldGenerateMongoIds($value)
    {
        $type = Type::getType('id');

        $this->assertInstanceOf('MongoId', $type->convertToDatabaseValue($value));
    }

    public function provideInvalidMongoIdConstructorArguments()
    {
        return array(
            'integer' => array(1),
            'float'   => array(3.14),
            'string'  => array('string'),
            'bool'    => array(true),
            'object'  => array(array('x' => 1, 'y' => 2)),
        );
    }
}
