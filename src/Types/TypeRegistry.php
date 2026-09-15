<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use Generator;
use InvalidArgumentException;
use IteratorAggregate;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use ReflectionClass;

use function array_key_exists;
use function array_keys;
use function assert;
use function get_debug_type;
use function is_subclass_of;
use function sprintf;

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

    /**
     * Map of type names to the container service IDs providing them.
     *
     * @var array<string, string>
     */
    private array $serviceIds = [];

    private ?ContainerInterface $container = null;

    private static ?TypeRegistry $sharedInstance = null;

    /**
     * Creates a registry pre-populated with all built-in types. Additional types passed via
     * {@param $types} are registered on top; if a name matches a built-in type it is
     * overridden rather than re-registered.
     *
     * A {@see ContainerInterface} can be passed instead of an array to lazy-load type instances
     * from a service container. In that case, {@param $serviceIds} maps type names to the
     * container service IDs providing them. Types are resolved on first access and cached.
     *
     * @param array<string, Type|class-string<Type>>|ContainerInterface $types
     * @param array<string, string>|null                                $serviceIds Map of type names to container
     *                                                                              service IDs. Required when passing
     *                                                                              a container, in which case an empty
     *                                                                              map means no types beyond the
     *                                                                              built-in ones.
     */
    public function __construct(array|ContainerInterface $types = [], ?array $serviceIds = null)
    {
        if ($types instanceof ContainerInterface) {
            if ($serviceIds === null) {
                throw new InvalidArgumentException(sprintf('A map of type names to service IDs is required when passing a "%s".', ContainerInterface::class));
            }

            $this->container  = $types;
            $this->serviceIds = $serviceIds;

            return;
        }

        if ($serviceIds !== null) {
            throw new InvalidArgumentException(sprintf('A map of type names to service IDs can only be used together with a "%s".', ContainerInterface::class));
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
        // Dropping the service ID keeps an unresolved container-backed type from being
        // instantiated only to be discarded.
        unset($this->serviceIds[$name]);

        if ($type instanceof Type) {
            $this->typesMap[$name]    = $type::class;
            $this->typeObjects[$name] = $type;

            return;
        }

        if (! is_subclass_of($type, Type::class)) {
            throw InvalidTypeException::invalidTypeClass($name, $type);
        }

        $constructor = (new ReflectionClass($type))->getConstructor();
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
            || array_key_exists($name, $this->serviceIds)
            || array_key_exists($name, self::BUILTIN_TYPES_MAP);
    }

    /**
     * Get a Type instance.
     *
     * @throws InvalidTypeException
     */
    public function get(string $name): Type
    {
        $type = $this->typeObjects[$name] ?? null;
        if ($type !== null) {
            return $type;
        }

        if (array_key_exists($name, $this->serviceIds)) {
            return $this->typeObjects[$name] = $this->resolveService($name);
        }

        $class = $this->typesMap[$name] ?? self::BUILTIN_TYPES_MAP[$name] ?? null;
        if ($class === null) {
            throw InvalidTypeException::invalidTypeName($name);
        }

        return $this->typeObjects[$name] = new $class();
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
        $seen = [];

        foreach ($this->typeObjects as $name => $type) {
            $seen[$name] = true;

            yield $name => $type;
        }

        // Resolving adds to $this->typeObjects, which is why each name is tracked in $seen instead.
        foreach ([$this->typesMap, $this->serviceIds, self::BUILTIN_TYPES_MAP] as $names) {
            foreach (array_keys($names) as $name) {
                if (isset($seen[$name])) {
                    continue;
                }

                $seen[$name] = true;

                yield $name => $this->get($name);
            }
        }
    }

    /** @throws InvalidTypeException */
    private function resolveService(string $name): Type
    {
        $container = $this->container;
        assert($container !== null);

        $serviceId = $this->serviceIds[$name];

        try {
            $type = $container->get($serviceId);
        } catch (ContainerExceptionInterface $exception) {
            if (! $container->has($serviceId)) {
                throw InvalidTypeException::serviceNotFound($name, $serviceId, $exception);
            }

            throw $exception;
        }

        if (! $type instanceof Type) {
            throw InvalidTypeException::invalidServiceType($name, $serviceId, get_debug_type($type));
        }

        return $type;
    }

    /** @internal Do not use this method. */
    public static function getSharedInstance(): TypeRegistry
    {
        return self::$sharedInstance ??= new self();
    }
}
