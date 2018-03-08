<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;

class IdTypeTest extends TestCase
{
    public function testConvertToDatabaseValue()
    {
        $identifier = new ObjectId();
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

        $this->assertInstanceOf(ObjectId::class, $type->convertToDatabaseValue($value));
    }

    public function provideInvalidObjectIdConstructorArguments()
    {
        return [
            'integer' => [1],
            'float'   => [3.14],
            'string'  => ['string'],
            'bool'    => [true],
        ];
    }
}
