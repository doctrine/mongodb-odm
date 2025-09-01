<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Types;
use Symfony\Component\Uid\Uuid;

use function end;
use function explode;
use function gettype;
use function is_object;
use function str_replace;

/**
 * The Type interface.
 */
abstract class Type
{
    public const ID                 = 'id';
    public const CUSTOMID           = 'custom_id';
    public const BOOL               = 'bool';
    public const INT                = 'int';
    public const INT64              = 'int64';
    public const FLOAT              = 'float';
    public const STRING             = 'string';
    public const DATE               = 'date';
    public const DATE_IMMUTABLE     = 'date_immutable';
    public const KEY                = 'key';
    public const TIMESTAMP          = 'timestamp';
    public const BINDATA            = 'bin';
    public const BINDATAFUNC        = 'bin_func';
    public const BINDATABYTEARRAY   = 'bin_bytearray';
    public const BINDATAUUID        = 'bin_uuid';
    public const BINDATAUUIDRFC4122 = 'bin_uuid_rfc4122';
    public const BINDATAMD5         = 'bin_md5';
    public const BINDATACUSTOM      = 'bin_custom';
    public const HASH               = 'hash';
    public const COLLECTION         = 'collection';
    public const OBJECTID           = 'object_id';
    public const RAW                = 'raw';
    public const DECIMAL128         = 'decimal128';
    public const UUID               = 'uuid';
    public const VECTOR_FLOAT32     = 'vector_float32';
    public const VECTOR_INT8        = 'vector_int8';
    public const VECTOR_PACKED_BIT  = 'vector_packed_bit';

    /** @deprecated const was deprecated in doctrine/mongodb-odm 2.1 and will be removed in 3.0. Use Type::INT instead */
    public const INTID = 'int_id';

    /** @deprecated const was deprecated in doctrine/mongodb-odm 2.1 and will be removed in 3.0. Use Type::INT instead */
    public const INTEGER = 'integer';

    /** @deprecated const was deprecated in doctrine/mongodb-odm 2.1 and will be removed in 3.0. Use Type::BOOL instead */
    public const BOOLEAN = 'boolean';

    /** @var Type[] Map of already instantiated type objects. One instance per type (flyweight). */
    private static array $typeObjects = [];

    /** @var array<string, class-string> The map of supported doctrine mapping types. */
    private static array $typesMap = [
        self::ID => Types\IdType::class,
        self::INTID => Types\IntIdType::class,
        self::CUSTOMID => Types\CustomIdType::class,
        self::BOOL => Types\BooleanType::class,
        self::BOOLEAN => Types\BooleanType::class,
        self::INT => Types\IntType::class,
        self::INTEGER => Types\IntType::class,
        self::INT64 => Types\Int64Type::class,
        self::FLOAT => Types\FloatType::class,
        self::STRING => Types\StringType::class,
        self::DATE => Types\DateType::class,
        self::DATE_IMMUTABLE => Types\DateImmutableType::class,
        self::KEY => Types\KeyType::class,
        self::TIMESTAMP => Types\TimestampType::class,
        self::BINDATA => Types\BinDataType::class,
        self::BINDATAFUNC => Types\BinDataFuncType::class,
        self::BINDATABYTEARRAY => Types\BinDataByteArrayType::class,
        self::BINDATAUUID => Types\BinDataUUIDType::class,
        self::BINDATAUUIDRFC4122 => Types\BinDataUUIDRFC4122Type::class,
        self::BINDATAMD5 => Types\BinDataMD5Type::class,
        self::BINDATACUSTOM => Types\BinDataCustomType::class,
        self::HASH => Types\HashType::class,
        self::COLLECTION => Types\CollectionType::class,
        self::OBJECTID => Types\ObjectIdType::class,
        self::RAW => Types\RawType::class,
        self::DECIMAL128 => Types\Decimal128Type::class,
        self::UUID => Types\BinaryUuidType::class,
        self::VECTOR_FLOAT32 => Types\VectorFloat32Type::class,
        self::VECTOR_INT8 => Types\VectorInt8Type::class,
        self::VECTOR_PACKED_BIT => Types\VectorPackedBitType::class,
    ];

    /** Prevent instantiation and force use of the factory method. */
    final private function __construct()
    {
    }

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed $value The value to convert.
     *
     * @return mixed The database representation of the value.
     */
    public function convertToDatabaseValue($value)
    {
        return $value;
    }

    /**
     * Converts a value from its database representation to its PHP representation
     * of this type.
     *
     * @param mixed $value The value to convert.
     *
     * @return mixed The PHP representation of the value.
     */
    public function convertToPHPValue($value)
    {
        return $value;
    }

    /**
     * Get the PHP code equivalent to {@see convertToDatabaseValue()}, used in code generator.
     * Use variables $value for input and $return for output.
     */
    public function closureToMongo(): string
    {
        return '$return = $value;';
    }

    /**
     * Get the PHP code equivalent to {@see convertToPHPValue()}, used in code generator.
     * Use variables $value for input and $return for output.
     */
    public function closureToPHP(): string
    {
        return '$return = $value;';
    }

    /**
     * Register a new type in the type map.
     */
    public static function registerType(string $name, string $class): void
    {
        self::$typesMap[$name] = $class;
    }

    /**
     * Get a Type instance.
     *
     * @throws InvalidTypeException
     */
    public static function getType(string $type): Type
    {
        if (! isset(self::$typesMap[$type])) {
            throw InvalidTypeException::invalidTypeName($type);
        }

        return self::$typeObjects[$type] ??= new (self::$typesMap[$type]);
    }

    /**
     * Get a Type instance based on the type of the passed php variable.
     *
     * @param mixed $variable
     */
    public static function getTypeFromPHPVariable($variable): ?Type
    {
        if (is_object($variable)) {
            if ($variable instanceof DateTimeImmutable) {
                return self::getType(self::DATE_IMMUTABLE);
            }

            if ($variable instanceof DateTimeInterface) {
                return self::getType(self::DATE);
            }

            if ($variable instanceof Uuid) {
                return self::getType(self::UUID);
            }

            // Try the variable class as type name
            if (self::hasType($variable::class)) {
                return self::getType($variable::class);
            }

            return null;
        }

        return match (gettype($variable)) {
            'integer' => self::getType(self::INT),
            'boolean' => self::getType(self::BOOL),
            'double' => self::getType(self::FLOAT),
            'string' => self::getType(self::STRING),
            default => null,
        };
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public static function convertPHPToDatabaseValue($value)
    {
        $type = self::getTypeFromPHPVariable($value);
        if ($type !== null) {
            return $type->convertToDatabaseValue($value);
        }

        return $value;
    }

    /**
     * Adds a custom type to the type map.
     *
     * @param class-string $className
     *
     * @throws MappingException
     *
     * @static
     */
    public static function addType(string $name, string $className): void
    {
        if (isset(self::$typesMap[$name])) {
            throw MappingException::typeExists($name);
        }

        self::$typesMap[$name] = $className;
    }

    /**
     * Checks if exists support for a type.
     *
     * @static
     */
    public static function hasType(string $name): bool
    {
        return isset(self::$typesMap[$name]);
    }

    /**
     * Overrides an already defined type to use a different implementation.
     *
     * @param class-string $className
     *
     * @throws MappingException
     *
     * @static
     */
    public static function overrideType(string $name, string $className): void
    {
        if (! isset(self::$typesMap[$name])) {
            throw MappingException::typeNotFound($name);
        }

        self::$typesMap[$name] = $className;
    }

    /**
     * Get the types array map which holds all registered types and the corresponding
     * type class
     *
     * @phpstan-return array<string, class-string>
     */
    public static function getTypesMap(): array
    {
        return self::$typesMap;
    }

    public function __toString(): string
    {
        $e         = explode('\\', static::class);
        $className = end($e);

        return str_replace('Type', '', $className);
    }
}
