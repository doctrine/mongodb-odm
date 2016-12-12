<?php

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\ODM\MongoDB\Types\Type;
use PHPUnit\Framework\TestCase;

class IdTypeTest extends TestCase
{
    public function testConvertToDatabaseValue()
    {
        $identifier = new \MongoDB\BSON\ObjectId();
        $type = Type::getType('id');

        $this->assertNull($type->convertToDatabaseValue(null), 'null is not converted');
        $this->assertSame($identifier, $type->convertToDatabaseValue($identifier), 'ObjectId objects are not converted');
        $this->assertEquals($identifier, $type->convertToDatabaseValue((string) $identifier), 'ObjectId strings are converted to ObjectId objects');
    }

    /**
     * @dataProvider provideInvalidObjectIdConstructorArguments
     */
    public function testConvertToDatabaseValueShouldGenerateObjectIds($value)
    {
        $type = Type::getType('id');

        $this->assertInstanceOf(\MongoDB\BSON\ObjectId::class, $type->convertToDatabaseValue($value));
    }

    public function provideInvalidObjectIdConstructorArguments()
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
