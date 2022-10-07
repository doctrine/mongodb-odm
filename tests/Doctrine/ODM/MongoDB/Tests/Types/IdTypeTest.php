<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\TestCase;

class IdTypeTest extends TestCase
{
    public function testConvertToDatabaseValue(): void
    {
        $identifier = new ObjectId();
        $type       = Type::getType('id');

        self::assertNull($type->convertToDatabaseValue(null), 'null is not converted');
        self::assertSame($identifier, $type->convertToDatabaseValue($identifier), 'ObjectId objects are not converted');
        self::assertEquals($identifier, $type->convertToDatabaseValue((string) $identifier), 'ObjectId strings are converted to ObjectId objects');
    }

    /**
     * @param mixed $value
     *
     * @dataProvider provideInvalidObjectIdConstructorArguments
     */
    public function testConvertToDatabaseValueShouldGenerateObjectIds($value): void
    {
        $type = Type::getType('id');

        self::assertInstanceOf(ObjectId::class, $type->convertToDatabaseValue($value));
    }

    public function provideInvalidObjectIdConstructorArguments(): array
    {
        return [
            'integer' => [1],
            'float'   => [3.14],
            'string'  => ['string'],
            'bool'    => [true],
        ];
    }
}
