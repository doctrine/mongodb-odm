<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping;

use ArrayAccess;
use Countable;
use Doctrine\ODM\MongoDB\Mapping\PropertyAccessors\ReflectionReadonlyProperty;
use Doctrine\Persistence\Mapping\ReflectionService;
use Doctrine\Persistence\Reflection\EnumReflectionProperty;
use Generator;
use IteratorAggregate;
use OutOfBoundsException;
use ReflectionProperty;
use Traversable;

use function array_keys;
use function assert;
use function count;
use function trigger_deprecation;

/**
 * @internal
 *
 * @template-implements ArrayAccess<string, ReflectionProperty|null>
 * @template-implements IteratorAggregate<string, ReflectionProperty|null>
 */
class LegacyReflectionFields implements ArrayAccess, IteratorAggregate, Countable
{
    /** @var array<string, ReflectionProperty|null> */
    private array $reflFields = [];

    public function __construct(private ClassMetadata $classMetadata, private ReflectionService $reflectionService)
    {
    }

    /** @param string $offset */
    public function offsetExists($offset): bool // phpcs:ignore
    {
        trigger_deprecation('doctrine/mongodb-odm', '2.14', 'Access to ClassMetadata::$reflFields is deprecated and will be removed in Doctrine ODM 3.0.');

        return isset($this->classMetadata->propertyAccessors[$offset]);
    }

    /**
     * @param string $field
     *
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function offsetGet($field): mixed // phpcs:ignore
    {
        if (isset($this->reflFields[$field])) {
            return $this->reflFields[$field];
        }

        trigger_deprecation('doctrine/mongodb-odm', '2.14', 'Access to ClassMetadata::$reflFields is deprecated and will be removed in Doctrine ODM 3.0.');

        if (! isset($this->classMetadata->propertyAccessors[$field])) {
            throw new OutOfBoundsException('Unknown field: ' . $this->classMetadata->name . ' ::$' . $field);
        }

        $className = $this->classMetadata->fieldMappings[$field]['inherited']
            ?? $this->classMetadata->fieldMappings[$field]['declared']
            ?? $this->classMetadata->associationMappings[$field]['declared']
            ?? $this->classMetadata->name;

        $this->reflFields[$field] = $this->getAccessibleProperty($className, $field);

        if (isset($this->classMetadata->fieldMappings[$field])) {
            if ($this->classMetadata->fieldMappings[$field]['enumType'] ?? null) {
                $this->reflFields[$field] = new EnumReflectionProperty(
                    $this->reflFields[$field],
                    $this->classMetadata->fieldMappings[$field]['enumType'],
                );
            }
        }

        return $this->reflFields[$field];
    }

    /**
     * @param string             $offset
     * @param ReflectionProperty $value
     */
    public function offsetSet($offset, $value): void // phpcs:ignore
    {
        $this->reflFields[$offset] = $value;
    }

    /** @param string $offset */
    public function offsetUnset($offset): void // phpcs:ignore
    {
        unset($this->reflFields[$offset]);
    }

    /** @psalm-param class-string $class */
    private function getAccessibleProperty(string $class, string $field): ReflectionProperty
    {
        $reflectionProperty = $this->reflectionService->getAccessibleProperty($class, $field);

        assert($reflectionProperty !== null);

        if ($reflectionProperty->isReadOnly()) {
            $declaringClass = $reflectionProperty->class;
            if ($declaringClass !== $class) {
                $reflectionProperty = $this->reflectionService->getAccessibleProperty($declaringClass, $field);

                assert($reflectionProperty !== null);
            }

            $reflectionProperty = new ReflectionReadonlyProperty($reflectionProperty);
        }

        return $reflectionProperty;
    }

    /** @return Generator<string, ReflectionProperty> */
    public function getIterator(): Traversable
    {
        trigger_deprecation('doctrine/mongodb-odm', '2.14', 'Access to ClassMetadata::$reflFields is deprecated and will be removed in Doctrine ODM 3.0.');

        $keys = array_keys($this->classMetadata->propertyAccessors);

        foreach ($keys as $key) {
            yield $key => $this->offsetGet($key);
        }
    }

    public function count(): int
    {
        trigger_deprecation('doctrine/mongodb-odm', '2.14', 'Access to ClassMetadata::$reflFields is deprecated and will be removed in Doctrine ODM 3.0.');

        return count($this->classMetadata->propertyAccessors);
    }
}
