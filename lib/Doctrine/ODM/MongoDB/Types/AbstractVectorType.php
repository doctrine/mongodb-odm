<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use InvalidArgumentException;
use MongoDB\BSON\Binary;
use MongoDB\BSON\VectorType;

use function enum_exists;
use function get_debug_type;
use function is_array;
use function sprintf;
use function str_replace;

/** @internal */
abstract class AbstractVectorType extends Type
{
    public function convertToDatabaseValue(mixed $value): ?Binary
    {
        if (! enum_exists(VectorType::class)) {
            throw new InvalidArgumentException('MongoDB\BSON\VectorType enum does not exist. Install the MongoDB Extension version 2.2.0 or higher in order to use a vector field type.');
        }

        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return Binary::fromVector($value, $this->getVectorType());
        }

        if (! $value instanceof Binary) {
            throw new InvalidArgumentException(sprintf('Invalid data type %s received for vector field, expected null, array or MongoDB\BSON\Binary', get_debug_type($value)));
        }

        if ($value->getType() !== Binary::TYPE_VECTOR) {
            throw new InvalidArgumentException(sprintf('Invalid binary data of type %d received for vector field, expected binary type %d', $value->getType(), Binary::TYPE_VECTOR));
        }

        if ($value->getVectorType() !== $this->getVectorType()) {
            throw new InvalidArgumentException(sprintf('Invalid binary vector data of vector type %s received for vector field, expected vector type %s', $value->getVectorType()->name, $this->getVectorType()->name));
        }

        return $value;
    }

    /** @return list<float>|list<int>|list<bool>|null */
    public function convertToPHPValue(mixed $value): ?array
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        if (! $value instanceof Binary) {
            throw new InvalidArgumentException(sprintf('Invalid data of type "%s" received for vector field', get_debug_type($value)));
        }

        if ($value->getType() !== Binary::TYPE_VECTOR) {
            throw new InvalidArgumentException(sprintf('Invalid binary data of type %d received for vector field', $value->getType()));
        }

        if ($value->getVectorType() !== $this->getVectorType()) {
            throw new InvalidArgumentException(sprintf('Invalid binary vector data of vector type %s received for vector field, expected vector type %s', $value->getVectorType()->name, $this->getVectorType()->name));
        }

        return $value->toArray();
    }

    public function closureToMongo(): string
    {
        return str_replace('%%vectorType%%', $this->getVectorType()->name, <<<'PHP'
            if ($value === null) {
                $return = null;
            } elseif (\is_array($value)) {
                $return = \MongoDB\BSON\Binary::fromVector($value, \MongoDB\BSON\VectorType::%%vectorType%%);
            } elseif (! $value instanceof \MongoDB\BSON\Binary) {
                throw new InvalidArgumentException(sprintf('Invalid data type %s received for vector field, expected null, array or MongoDB\BSON\Binary', get_debug_type($value)));
            } elseif ($value->getType() !== \MongoDB\BSON\Binary::TYPE_VECTOR) {
                throw new InvalidArgumentException(sprintf('Invalid binary data of type %d received for vector field, expected binary type %d', $value->getType(), \MongoDB\BSON\Binary::TYPE_VECTOR));
            } elseif ($value->getVectorType() !== \MongoDB\BSON\VectorType::%%vectorType%%) {
                throw new \InvalidArgumentException(sprintf('Invalid binary vector data of vector type %s received for vector field, expected vector type %%vectorType%%', $value->getVectorType()->name));
            } else {
                $return = $value;
            }
PHP);
    }

    public function closureToPHP(): string
    {
        return str_replace('%%vectorType%%', $this->getVectorType()->name, <<<'PHP'
            if ($value === null) {
                $return = null;
            } elseif (\is_array($value)) {
                $return = $value;
            } elseif (! $value instanceof \MongoDB\BSON\Binary) {
                throw new \InvalidArgumentException(sprintf('Invalid data of type "%s" received for vector field', get_debug_type($value)));
            } elseif ($value->getType() !== \MongoDB\BSON\Binary::TYPE_VECTOR) {
                throw new \InvalidArgumentException(sprintf('Invalid binary data of type %d received for vector field', $value->getType()));
            } elseif ($value->getVectorType() !== \MongoDB\BSON\VectorType::%%vectorType%%) {
                throw new \InvalidArgumentException(sprintf('Invalid binary vector data of vector type %s received for vector field, expected vector type %%vectorType%%', $value->getVectorType()->name));
            } else {
                $return = $value->toArray();
            }
PHP);
    }

    abstract protected function getVectorType(): VectorType;
}
