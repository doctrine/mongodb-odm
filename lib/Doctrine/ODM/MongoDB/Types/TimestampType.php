<?php

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\Timestamp;

/**
 * The Timestamp type.
 *
 * @since       1.0
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

    /**
     * @param Timestamp $timestamp
     * @return int
     */
    private function extractSeconds(Timestamp $timestamp)
    {
            $parts = explode(':', substr((string) $timestamp, 1, -1));
            return (int) $parts[1];
    }
}
