<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

/**
 * Provides value equality by comparing the BSON representation of the values.
 *
 * Use this trait in a type that implements {@see EquatableType} when the
 * database representation can be compared with a loose comparison. This works
 * for scalars and for BSON objects such as UTCDateTime or ObjectId.
 */
trait BsonValueEquality
{
    final public function valuesAreEqual(object $a, object $b): bool
    {
        // Loose comparison is required to compare BSON objects field by field
        // instead of by object identity.
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
        return $this->convertToDatabaseValue($a) == $this->convertToDatabaseValue($b);
    }
}
