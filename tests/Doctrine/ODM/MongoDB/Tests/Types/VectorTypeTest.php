<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\ODM\MongoDB\Types\Type;
use InvalidArgumentException;
use MongoDB\BSON\Binary;
use MongoDB\BSON\VectorType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('mongodb', '>= 2.2')]
class VectorTypeTest extends TestCase
{
    #[DataProvider('providePhpVectors')]
    public function testConvertToDatabaseValue(string $name, mixed $value, mixed $expectedValue): void
    {
        $this->assertEquals($expectedValue, Type::getType($name)->convertToDatabaseValue($value));
    }

    #[DataProvider('providePhpVectors')]
    public function testClosureToDatabase(string $name, mixed $value, mixed $expectedValue): void
    {
        $return = $this;
        eval(Type::getType($name)->closureToMongo());

        $this->assertEquals($expectedValue, $return);
    }

    /** @return iterable<array{0: Type::VECTOR_*, 1: mixed, 2: mixed}> */
    public static function providePhpVectors(): iterable
    {
        $array  = [1.0, 2.0, 3.0, 4.0];
        $binary = Binary::fromVector($array, VectorType::Float32);

        yield [Type::VECTOR_FLOAT32, null, null];
        yield [Type::VECTOR_FLOAT32, $array, $binary];
        yield [Type::VECTOR_FLOAT32, $binary, $binary];

        $array  = [1, 2, 3, 4];
        $binary = Binary::fromVector($array, VectorType::Int8);

        yield [Type::VECTOR_INT8, null, null];
        yield [Type::VECTOR_INT8, $array, $binary];
        yield [Type::VECTOR_INT8, $binary, $binary];

        $array  = [true, false, 0, 1];
        $binary = Binary::fromVector($array, VectorType::PackedBit);

        yield [Type::VECTOR_PACKED_BIT, null, null];
        yield [Type::VECTOR_PACKED_BIT, $array, $binary];
        yield [Type::VECTOR_PACKED_BIT, $binary, $binary];
    }

    #[DataProvider('provideDatabaseVectors')]
    public function testConvertToPHPValue(string $name, mixed $value, mixed $expectedValue): void
    {
        $this->assertEquals($expectedValue, Type::getType($name)->convertToPHPValue($value));
    }

    #[DataProvider('provideDatabaseVectors')]
    public function testClosureToPHP(string $name, mixed $value, mixed $expectedValue): void
    {
        $return = $this;
        eval(Type::getType($name)->closureToPHP());

        $this->assertEquals($expectedValue, $return);
    }

    /** @return iterable<array{0: Type::VECTOR_*, 1: mixed, 2: mixed}> */
    public static function provideDatabaseVectors(): iterable
    {
        $array  = [1.0, 2.0, 3.0, 4.0];
        $binary = Binary::fromVector($array, VectorType::Float32);

        yield [Type::VECTOR_FLOAT32, null, null];
        yield [Type::VECTOR_FLOAT32, $array, $array];
        yield [Type::VECTOR_FLOAT32, $binary, $array];

        $array  = [1, 2, 3, 4];
        $binary = Binary::fromVector($array, VectorType::Int8);

        yield [Type::VECTOR_INT8, null, null];
        yield [Type::VECTOR_INT8, $array, $array];
        yield [Type::VECTOR_INT8, $binary, $array];

        $array  = [true, false, 0, 1];
        $binary = Binary::fromVector($array, VectorType::PackedBit);

        yield [Type::VECTOR_PACKED_BIT, null, null];
        yield [Type::VECTOR_PACKED_BIT, $array, $array];
        yield [Type::VECTOR_PACKED_BIT, $binary, $array];
    }

    #[DataProvider('provideDatabaseValueException')]
    public function testConvertToPHPValueException(mixed $value, string $message): void
    {
        $type = Type::getType(Type::VECTOR_FLOAT32);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $type->convertToDatabaseValue($value);
    }

    #[DataProvider('provideDatabaseValueException')]
    public function testClosureToPHPValueException(mixed $value, string $message): void
    {
        $type = Type::getType(Type::VECTOR_FLOAT32);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        eval($type->closureToMongo());
    }

    /** @return iterable<array{0: mixed, 1: string}> */
    public static function provideDatabaseValueException(): iterable
    {
        yield ['invalid', 'Invalid data type string received for vector field, expected null, array or MongoDB\BSON\Binary'];
        yield [new Binary("\x03\x00\x01\x02\x03", Binary::TYPE_GENERIC), 'Invalid binary data of type 0 received for vector field'];
        yield [Binary::fromVector([1, 2, 3], VectorType::Int8), 'Invalid binary vector data of vector type Int8 received for vector field, expected vector type Float32'];
    }

    #[DataProvider('providePHPValueException')]
    public function testConvertToDatabaseValueException(mixed $value, string $message): void
    {
        $type = Type::getType(Type::VECTOR_FLOAT32);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        $type->convertToPHPValue($value);
    }

    #[DataProvider('providePHPValueException')]
    public function testClosureToDatabaseException(mixed $value, string $message): void
    {
        $type = Type::getType(Type::VECTOR_FLOAT32);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        eval($type->closureToPHP());
    }

    public static function providePHPValueException(): iterable
    {
        yield ['invalid', 'Invalid data of type "string" received for vector field'];
        yield [new Binary("\x03\x00\x01\x02\x03", Binary::TYPE_GENERIC), 'Invalid binary data of type 0 received for vector field'];
        yield [Binary::fromVector([1, 2, 3], VectorType::Int8), 'Invalid binary vector data of vector type Int8 received for vector field, expected vector type Float32'];
    }
}
