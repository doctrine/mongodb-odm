<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\Uid\Uuid;

use function gettype;
use function is_object;

/**
 * Determines the mapping type to use for a value that has no declared type.
 *
 * @internal
 */
final class TypeGuesser
{
    public function __construct(private readonly TypeProvider $types)
    {
    }

    /**
     * Determine the database representation of a value based on its PHP type.
     */
    public function convertToDatabaseValue(mixed $value): mixed
    {
        $type = $this->guessTypeFromValue($value);

        return $type === null ? $value : $type->convertToDatabaseValue($value);
    }

    /**
     * Get a Type instance based on the type of the passed PHP variable.
     */
    public function guessTypeFromValue(mixed $value): ?Type
    {
        if (is_object($value)) {
            if ($value instanceof DateTimeImmutable) {
                return $this->types->get(Type::DATE_IMMUTABLE);
            }

            if ($value instanceof DateTimeInterface) {
                return $this->types->get(Type::DATE);
            }

            if ($value instanceof Uuid) {
                return $this->types->get(Type::UUID);
            }

            // Try the variable class as a type name
            if ($this->types->has($value::class)) {
                return $this->types->get($value::class);
            }

            return null;
        }

        return match (gettype($value)) {
            'integer' => $this->types->get(Type::INT),
            'boolean' => $this->types->get(Type::BOOL),
            'double' => $this->types->get(Type::FLOAT),
            'string' => $this->types->get(Type::STRING),
            default => null,
        };
    }
}
