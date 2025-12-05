<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use DateTimeImmutable;
use DateTimeInterface;
use ReflectionClass;
use Symfony\Component\Uid\Uuid;

use function gettype;
use function is_object;

/**
 * The TypeRegistry is responsible for managing the mapping types supported.
 */
class TypeRegistry
{
    /** @var array<string, class-string<Type>> The map of supported mapping types. */
    private array $typesMap = [
        Type::ID => IdType::class,
        Type::INTID => IntIdType::class,
        Type::CUSTOMID => CustomIdType::class,
        Type::BOOL => BooleanType::class,
        Type::BOOLEAN => BooleanType::class,
        Type::INT => IntType::class,
        Type::INTEGER => IntType::class,
        Type::INT64 => Int64Type::class,
        Type::FLOAT => FloatType::class,
        Type::STRING => StringType::class,
        Type::DATE => DateType::class,
        Type::DATE_IMMUTABLE => DateImmutableType::class,
        Type::KEY => KeyType::class,
        Type::TIMESTAMP => TimestampType::class,
        Type::BINDATA => BinDataType::class,
        Type::BINDATAFUNC => BinDataFuncType::class,
        Type::BINDATABYTEARRAY => BinDataByteArrayType::class,
        Type::BINDATAUUID => BinDataUUIDType::class,
        Type::BINDATAUUIDRFC4122 => BinDataUUIDRFC4122Type::class,
        Type::BINDATAMD5 => BinDataMD5Type::class,
        Type::BINDATACUSTOM => BinDataCustomType::class,
        Type::HASH => HashType::class,
        Type::COLLECTION => CollectionType::class,
        Type::OBJECTID => ObjectIdType::class,
        Type::RAW => RawType::class,
        Type::DECIMAL128 => Decimal128Type::class,
        Type::UUID => BinaryUuidType::class,
        Type::VECTOR_FLOAT32 => VectorFloat32Type::class,
        Type::VECTOR_INT8 => VectorInt8Type::class,
        Type::VECTOR_PACKED_BIT => VectorPackedBitType::class,
    ];

    /** @var array<string, Type> Cache of instantiated Type objects */
    private array $typeObjects = [];

    /**
     * Register a new type in the type map.
     *
     * The name of the type can be a PHP class name used for automatic type detection
     *
     * @param non-empty-string   $name
     * @param class-string<Type> $class
     */
    public function register(string $name, string $class): void
    {
        $this->typesMap[$name] = $class;
        unset($this->typeObjects[$name]);
    }

    /**
     * Checks if exists support for a type.
     */
    public function has(string $name): bool
    {
        return isset($this->typesMap[$name]);
    }

    /**
     * Get a Type instance.
     *
     * @throws InvalidTypeException
     */
    public function get(string $name): Type
    {
        if (! isset($this->typesMap[$name])) {
            throw InvalidTypeException::invalidTypeName($name);
        }

        return $this->typeObjects[$name] ??= (new ReflectionClass($this->typesMap[$name]))->newInstanceWithoutConstructor();
    }

    /**
     * Get a Type instance based on the type of the passed PHP variable.
     */
    public function fromVariable(mixed $variable): ?Type
    {
        if (is_object($variable)) {
            if ($variable instanceof DateTimeImmutable) {
                return $this->get(Type::DATE_IMMUTABLE);
            }

            if ($variable instanceof DateTimeInterface) {
                return $this->get(Type::DATE);
            }

            if ($variable instanceof Uuid) {
                return $this->get(Type::UUID);
            }

            // Try the variable class as a type name
            if ($this->has($variable::class)) {
                return $this->get($variable::class);
            }

            return null;
        }

        return match (gettype($variable)) {
            'integer' => $this->get(Type::INT),
            'boolean' => $this->get(Type::BOOL),
            'double' => $this->get(Type::FLOAT),
            'string' => $this->get(Type::STRING),
            default => null,
        };
    }

    /**
     * Determine the database representation of a value based on its PHP type.
     */
    public function convertToDatabaseValue(mixed $value): mixed
    {
        $type = $this->fromVariable($value);

        if ($type === null) {
            return $value;
        }

        return $type->convertToDatabaseValue($value);
    }

    /**
     * Get the type array map which holds all registered types and the corresponding
     * type class
     *
     * @internal
     *
     * @return array<string, class-string<Type>>
     */
    public function getMap(): array
    {
        return $this->typesMap;
    }
}
