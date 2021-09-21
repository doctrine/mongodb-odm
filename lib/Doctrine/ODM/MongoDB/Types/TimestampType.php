<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Timestamp;

use function explode;
use function substr;

/**
 * The Timestamp type.
 */
class TimestampType extends Type
{
    public function convertToDatabaseValue($value)
    {
        if ($value instanceof Timestamp) {
            return $value;
        }

        return $value !== null ? new Timestamp(0, $value) : null;
    }

    public function convertToPHPValue($value)
    {
        return $value instanceof Timestamp ? $this->extractSeconds($value) : ($value !== null ? (string) $value : null);
    }

    private function extractSeconds(Timestamp $timestamp): int
    {
            $parts = explode(':', substr((string) $timestamp, 1, -1));

            return (int) $parts[1];
    }
}
