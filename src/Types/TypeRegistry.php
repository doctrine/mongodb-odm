<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use Generator;
use IteratorAggregate;
use ReflectionClass;
use Symfony\Contracts\Service\ServiceProviderInterface;

use function array_key_exists;
use function array_keys;
use function get_debug_type;
use function is_subclass_of;

/**
 * The TypeRegistry is responsible for managing the mapping types supported.
 *
 * @implements IteratorAggregate<string, Type>
 */
final class TypeRegistry implements TypeProvider, IteratorAggregate
{
    private const BUILTIN_TYPES_MAP = [
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

    /** @var array<string, class-string<Type>> Types registered by class name, not instantiated yet. */
    private array $typesMap = [];

    /** @var array<string, Type> Resolved types, keyed by type name. Doubles as the resolution cache. */
    private array $typeObjects = [];

    /** @var ServiceProviderInterface<mixed>|null */
    private ?ServiceProviderInterface $services = null;

    private static ?TypeRegistry $sharedInstance = null;

    /**
     * Creates a registry pre-populated with all built-in types. Additional types passed via
     * {@param $types} are registered on top; if a name matches a built-in type it is
     * overridden rather than re-registered.
     *
     * A {@see ServiceProviderInterface} can be passed instead of an array to lazy-load type
     * instances from a service container. Each service ID acts as the type name, so a service
     * providing type "foo" is exposed as type "foo". Types are resolved on first access and cached.
     *
     * @param array<string, Type|class-string<Type>>|ServiceProviderInterface<Type> $types
     */
    public function __construct(array|ServiceProviderInterface $types = [])
    {
        if ($types instanceof ServiceProviderInterface) {
            $this->services = $types;

            return;
        }

        foreach ($types as $name => $type) {
            $this->register($name, $type);
        }
    }

    /**
     * Register a new type in the type map, replacing any type previously registered under that name.
     *
     * The name of the type can be a PHP class name used for automatic type detection.
     *
     * @param class-string<Type>|Type $type
     */
    public function register(string $name, string|Type $type): void
    {
        if ($type instanceof Type) {
            $this->typeObjects[$name] = $type;
            unset($this->typesMap[$name]);

            return;
        }

        if (! is_subclass_of($type, Type::class)) {
            throw InvalidTypeException::invalidTypeClass($name, $type);
        }

        $reflectionClass = new ReflectionClass($type);
        if (! $reflectionClass->isInstantiable()) {
            throw InvalidTypeException::classNotInstantiable($type);
        }

        $constructor = $reflectionClass->getConstructor();
        if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
            throw InvalidTypeException::constructorHasRequiredParameters($type);
        }

        $this->typesMap[$name] = $type;
        unset($this->typeObjects[$name]);
    }

    /**
     * Checks if exists support for a type.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->typeObjects)
            || array_key_exists($name, $this->typesMap)
            || array_key_exists($name, self::BUILTIN_TYPES_MAP)
            || $this->services?->has($name);
    }

    /**
     * Get a Type instance.
     *
     * @throws InvalidTypeException
     */
    public function get(string $name): Type
    {
        if (isset($this->typeObjects[$name])) {
            return $this->typeObjects[$name];
        }

        if (isset($this->typesMap[$name])) {
            return $this->typeObjects[$name] = new ($this->typesMap[$name])();
        }

        if ($this->services?->has($name)) {
            $type = $this->services->get($name);

            if (! $type instanceof Type) {
                throw InvalidTypeException::invalidServiceType($name, get_debug_type($type));
            }

            return $type;
        }

        if (! isset(self::BUILTIN_TYPES_MAP[$name])) {
            throw InvalidTypeException::invalidTypeName($name);
        }

        return $this->typeObjects[$name] = new (self::BUILTIN_TYPES_MAP[$name])();
    }

    /**
     * Yields every known type, keyed by type name.
     *
     * Types that have not been resolved yet are instantiated as they are reached, so stopping the
     * iteration early leaves the remaining ones untouched.
     *
     * @return Generator<string, Type>
     *
     * @throws InvalidTypeException
     */
    public function getIterator(): Generator
    {
        $names = array_keys(self::BUILTIN_TYPES_MAP + $this->typeObjects + $this->typesMap + ($this->services?->getProvidedServices() ?? []));
        foreach ($names as $name) {
            yield $name => $this->get($name);
        }
    }

    /**
     * The shared instance is kept only for backward compatibility with the static methods of
     * {@see Type} and for callers that have not yet injected a dedicated registry.
     *
     * @internal Do not use this method. Inject a dedicated TypeRegistry instead.
     */
    public static function getDeprecatedSharedInstance(): TypeRegistry
    {
        return self::$sharedInstance ??= new self();
    }
}
