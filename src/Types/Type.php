<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use Doctrine\ODM\MongoDB\Mapping\MappingException;

use function end;
use function explode;
use function str_replace;
use function trigger_deprecation;

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

    private static ?TypeRegistry $registry = null;

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
     *
     * @deprecated Since 2.16, will be removed in 3.0.
     */
    public function closureToMongo(): string
    {
        trigger_deprecation('doctrine/mongodb-odm', '2.16', 'Type::closureToMongo() is deprecated and will be removed in 3.0.');

        return '$return = $value;';
    }

    /**
     * Get the PHP code equivalent to {@see convertToPHPValue()}, used in code generator.
     * Use variables $value for input and $return for output.
     *
     * @abstract The default implementation will change in 3.0.
     */
    public function closureToPHP(): string
    {
        trigger_deprecation('doctrine/mongodb-odm', '2.16', 'The method Type::closureToPHP() will change its default implementation in 3.0 to use convertToPHPValue(). Override this method if you need custom behavior before upgrading to 3.0 or use the trait ClosureToPHP to get the upcoming behavior now.');

        return '$return = $value;';
    }

    /**
     * Register a new type in the type map.
     *
     * @deprecated Use {@see TypeRegistry::register()} directly instead
     */
    public static function registerType(string $name, string $class): void
    {
        self::getRegistry()->register($name, $class);
    }

    /**
     * Get a Type instance.
     *
     * @deprecated Use {@see TypeRegistry::get()} directly instead
     *
     * @throws InvalidTypeException
     */
    public static function getType(string $type): Type
    {
        return self::getRegistry()->get($type);
    }

    /**
     * Get a Type instance based on the type of the passed php variable.
     *
     * @deprecated Use TypeRegistry::getFromPHPVariable() directly instead
     *
     * @param mixed $variable
     */
    public static function getTypeFromPHPVariable($variable): ?Type
    {
        return self::getRegistry()->fromVariable($variable);
    }

    /**
     * @deprecated Use TypeRegistry::fromVariable() to get the type
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function convertPHPToDatabaseValue($value)
    {
        return self::getRegistry()->convertToDatabaseValue($value);
    }

    /**
     * Adds a custom type to the type map.
     *
     * @deprecated Use {@see TypeRegistry::register()} directly instead
     *
     * @param class-string $className
     *
     * @throws MappingException
     *
     * @static
     */
    public static function addType(string $name, string $className): void
    {
        $registry = self::getRegistry();
        if ($registry->has($name)) {
            throw MappingException::typeExists($name);
        }

        $registry->register($name, $className);
    }

    /**
     * Checks if exists support for a type.
     *
     * @deprecated Use {@see TypeRegistry::has()} directly instead
     *
     * @static
     */
    public static function hasType(string $name): bool
    {
        return self::getRegistry()->has($name);
    }

    /**
     * Overrides an already defined type to use a different implementation.
     *
     * @deprecated Use {@see TypeRegistry::register()} directly instead
     *
     * @param class-string $className
     *
     * @throws MappingException
     *
     * @static
     */
    public static function overrideType(string $name, string $className): void
    {
        $registry = self::getRegistry();
        if (! $registry->has($name)) {
            throw MappingException::typeNotFound($name);
        }

        $registry->register($name, $className);
    }

    /**
     * Get the types array map which holds all registered types and the corresponding
     * type class
     *
     * @deprecated Will be removed in 3.0
     *
     * @phpstan-return array<string, class-string>
     */
    public static function getTypesMap(): array
    {
        return self::getRegistry()->getMap();
    }

    private static function getRegistry(): TypeRegistry
    {
        return self::$registry ??= new TypeRegistry();
    }

    public function __toString(): string
    {
        $e         = explode('\\', static::class);
        $className = end($e);

        return str_replace('Type', '', $className);
    }
}
