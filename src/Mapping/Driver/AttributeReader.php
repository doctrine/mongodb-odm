<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\Attribute\MappingAttribute;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function assert;
use function is_subclass_of;

/** @internal */
final class AttributeReader
{
    /** @return MappingAttribute[] */
    public function getClassAttributes(ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    /** @return MappingAttribute[] */
    public function getMethodAttributes(ReflectionMethod $method): array
    {
        return $this->convertToAttributeInstances($method->getAttributes());
    }

    /** @return MappingAttribute[] */
    public function getPropertyAttributes(ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    /**
     * @param ReflectionAttribute<object>[] $attributes
     *
     * @return MappingAttribute[]
     */
    private function convertToAttributeInstances(array $attributes): array
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            // Make sure we only get MongoDB ODM attribute classes
            if (! is_subclass_of($attributeName, MappingAttribute::class)) {
                continue;
            }

            $instance = $attribute->newInstance();
            assert($instance instanceof MappingAttribute);
            $instances[] = $instance;
        }

        return $instances;
    }
}
