<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\Annotations\Annotation;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

use function assert;
use function is_subclass_of;

/** @internal */
final class AttributeReader
{
    /** @return array<object> */
    public function getClassAttributes(ReflectionClass $class): array
    {
        return $this->convertToAttributeInstances($class->getAttributes());
    }

    /** @return array<object> */
    public function getMethodAttributes(ReflectionMethod $method): array
    {
        return $this->convertToAttributeInstances($method->getAttributes());
    }

    /** @return array<object> */
    public function getPropertyAttributes(ReflectionProperty $property): array
    {
        return $this->convertToAttributeInstances($property->getAttributes());
    }

    /**
     * @param ReflectionAttribute<object>[] $attributes
     *
     * @return Annotation[]
     */
    private function convertToAttributeInstances(array $attributes): array
    {
        $instances = [];

        foreach ($attributes as $attribute) {
            $attributeName = $attribute->getName();
            // Make sure we only get Doctrine Annotations
            if (! is_subclass_of($attributeName, Annotation::class)) {
                continue;
            }

            $instance = $attribute->newInstance();
            assert($instance instanceof Annotation);
            $instances[] = $instance;
        }

        return $instances;
    }
}
