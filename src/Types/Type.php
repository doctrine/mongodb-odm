<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use Stringable;

use function end;
use function explode;
use function str_replace;

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

    public function __toString(): string
    {
        $e         = explode('\\', static::class);
        $className = end($e);

        return str_replace('Type', '', $className);
    }
}
