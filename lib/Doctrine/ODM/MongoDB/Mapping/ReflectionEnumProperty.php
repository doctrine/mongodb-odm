<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping;

use BackedEnum;
use Doctrine\ODM\MongoDB\Hydrator\HydratorException;
use ReflectionProperty;
use ReturnTypeWillChange;
use ValueError;

use function assert;
use function get_class;
use function is_int;
use function is_string;

class ReflectionEnumProperty extends ReflectionProperty
{
    /** @var ReflectionProperty */
    private $originalReflectionProperty;

    /** @var class-string<BackedEnum> */
    private $enumType;

    /**
     * @param class-string<BackedEnum> $enumType
     */
    public function __construct(ReflectionProperty $originalReflectionProperty, string $enumType)
    {
        $this->originalReflectionProperty = $originalReflectionProperty;
        $this->enumType                   = $enumType;
    }

    /**
     * {@inheritDoc}
     *
     * @param object|null $object
     *
     * @return int|string|null
     */
    #[ReturnTypeWillChange]
    public function getValue($object = null)
    {
        if ($object === null) {
            return null;
        }

        return $this->originalReflectionProperty->getValue($object)->value;
    }

    /**
     * @param object $object
     * @param mixed  $value
     */
    public function setValue($object, $value = null): void
    {
        if ($value !== null) {
            $enumType = $this->enumType;
            try {
                $value = $enumType::from($value);
            } catch (ValueError $e) {
                assert(is_string($value) || is_int($value));

                throw HydratorException::invalidEnumValue(
                    get_class($object),
                    $this->originalReflectionProperty->getName(),
                    (string) $value,
                    $enumType,
                    $e
                );
            }
        }

        $this->originalReflectionProperty->setValue($object, $value);
    }
}
