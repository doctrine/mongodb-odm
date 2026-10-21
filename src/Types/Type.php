<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Stringable;

use function end;
use function explode;
use function str_replace;
use function trigger_deprecation;

/**
 * The Type interface.
 */
abstract class Type implements Stringable
{
    public const string ID                 = 'id';
    public const string CUSTOMID           = 'custom_id';
    public const string BOOL               = 'bool';
    public const string INT                = 'int';
    public const string INT64              = 'int64';
    public const string FLOAT              = 'float';
    public const string STRING             = 'string';
    public const string DATE               = 'date';
    public const string DATE_IMMUTABLE     = 'date_immutable';
    public const string KEY                = 'key';
    public const string TIMESTAMP          = 'timestamp';
    public const string BINDATA            = 'bin';
    public const string BINDATAFUNC        = 'bin_func';
    public const string BINDATABYTEARRAY   = 'bin_bytearray';
    public const string BINDATAUUID        = 'bin_uuid';
    public const string BINDATAUUIDRFC4122 = 'bin_uuid_rfc4122';
    public const string BINDATAMD5         = 'bin_md5';
    public const string BINDATACUSTOM      = 'bin_custom';
    public const string HASH               = 'hash';
    public const string COLLECTION         = 'collection';
    public const string OBJECTID           = 'object_id';
    public const string RAW                = 'raw';
    public const string DECIMAL128         = 'decimal128';
    public const string UUID               = 'uuid';
    public const string VECTOR_FLOAT32     = 'vector_float32';
    public const string VECTOR_INT8        = 'vector_int8';
    public const string VECTOR_PACKED_BIT  = 'vector_packed_bit';

    /**
     * Converts a value from its PHP representation to its database representation
     * of this type.
     *
     * @param mixed $value The value to convert.
     *
     * @return mixed The database representation of the value.
     */
    public function convertToDatabaseValue(mixed $value): mixed
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
    public function convertToPHPValue(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Get the PHP code equivalent to {@see convertToPHPValue()}, used in code generator.
     * By default, it calls the convertToPHPValue from child class.
     * Use variables $value for input and $return for output.
     */
    public function closureToPHP(): string
    {
        return '$return = $this->class->getFieldType($fieldName)->convertToPHPValue($value);';
    }

    /**
     * Register a new type in the type map.
     *
     * @deprecated Use {@see TypeRegistry::register()} instead
     */
    public static function registerType(string $name, string $class): void
    {
        trigger_deprecation('doctrine/mongodb-odm', '2.18', 'Type::registerType() is deprecated, use $typeRegistry->register() instead.');

        TypeRegistry::getDeprecatedSharedInstance()->register($name, $class);
    }

    /**
     * Get a Type instance.
     *
     * @deprecated Use {@see TypeRegistry::get()} instead
     *
     * @throws InvalidTypeException
     */
    public static function getType(string $type): Type
    {
        trigger_deprecation('doctrine/mongodb-odm', '2.18', 'Type::getType() is deprecated, use $typeProvider->get() instead.');

        return TypeRegistry::getDeprecatedSharedInstance()->get($type);
    }

    /**
     * Get a Type instance based on the type of the passed php variable.
     *
     * @deprecated Use {@see TypeGuesser::guessTypeFromValue()} instead
     */
    public static function getTypeFromPHPVariable(mixed $variable): ?Type
    {
        trigger_deprecation('doctrine/mongodb-odm', '2.18', 'Type::getTypeFromPHPVariable() is deprecated, use DocumentManager::getTypeGuesser()->guessTypeFromValue() instead.');

        return (new TypeGuesser(TypeRegistry::getDeprecatedSharedInstance()))->guessTypeFromValue($variable);
    }

    /** @deprecated Use {@see TypeGuesser::convertToDatabaseValue()} instead */
    public static function convertPHPToDatabaseValue(mixed $value): mixed
    {
        trigger_deprecation('doctrine/mongodb-odm', '2.18', 'Type::convertPHPToDatabaseValue() is deprecated, use DocumentManager::getTypeGuesser()->convertToDatabaseValue() instead.');

        return (new TypeGuesser(TypeRegistry::getDeprecatedSharedInstance()))->convertToDatabaseValue($value);
    }

    /**
     * Adds a custom type to the type map.
     *
     * @deprecated Use {@see TypeRegistry::register()} instead
     *
     * @param class-string $className
     *
     * @throws MappingException
     */
    public static function addType(string $name, string $className): void
    {
        trigger_deprecation('doctrine/mongodb-odm', '2.18', 'Type::addType() is deprecated, use $typeRegistry->register() instead.');

        $registry = TypeRegistry::getDeprecatedSharedInstance();
        if ($registry->has($name)) {
            throw MappingException::typeExists($name);
        }

        $registry->register($name, $className);
    }

    /**
     * Checks if exists support for a type.
     *
     * @deprecated Use {@see TypeRegistry::has()} instead
     */
    public static function hasType(string $name): bool
    {
        trigger_deprecation('doctrine/mongodb-odm', '2.18', 'Type::hasType() is deprecated, use $typeProvider->has() instead.');

        return TypeRegistry::getDeprecatedSharedInstance()->has($name);
    }

    /**
     * Overrides an already defined type to use a different implementation.
     *
     * @deprecated Use {@see TypeRegistry::register()} instead
     *
     * @param class-string $className
     *
     * @throws MappingException
     */
    public static function overrideType(string $name, string $className): void
    {
        trigger_deprecation('doctrine/mongodb-odm', '2.18', 'Type::overrideType() is deprecated, use $typeRegistry->register() instead.');

        $registry = TypeRegistry::getDeprecatedSharedInstance();
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
        trigger_deprecation('doctrine/mongodb-odm', '2.18', 'Type::getTypesMap() is deprecated and will be removed in 3.0. Iterate over the TypeProvider instead.');

        $map = [];
        foreach (TypeRegistry::getDeprecatedSharedInstance() as $name => $type) {
            $map[$name] = $type::class;
        }

        return $map;
    }

    public function __toString(): string
    {
        $e         = explode('\\', static::class);
        $className = end($e);

        return str_replace('Type', '', $className);
    }
}
