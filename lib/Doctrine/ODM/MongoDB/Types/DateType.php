<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use MongoDB\BSON\UTCDateTime;
use function date_default_timezone_get;
use function explode;
use function get_class;
use function gettype;
use function is_numeric;
use function is_scalar;
use function is_string;
use function sprintf;
use function str_pad;
use function strpos;

/**
 * The Date type.
 *
 */
class DateType extends Type
{
    /**
     * Converts a value to a DateTime.
     * Supports microseconds
     *
     * @throws InvalidArgumentException If $value is invalid.
     * @param  mixed $value \DateTimeInterface|\MongoDB\BSON\UTCDateTime|int|float
     */
    public static function getDateTime($value): \DateTimeInterface
    {
        $datetime = false;
        $exception = null;

        if ($value instanceof \DateTimeInterface) {
            return $value;
        } elseif ($value instanceof UTCDateTime) {
            $datetime = $value->toDateTime();
            $datetime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        } elseif (is_numeric($value)) {
            $seconds = $value;
            $value = (string) $value;
            $microseconds = 0;

            if (strpos($value, '.') !== false) {
                list($seconds, $microseconds) = explode('.', $value);
                $microseconds = str_pad($microseconds, 6, '0'); // ensure microseconds
            }

            $datetime = static::craftDateTime((int) $seconds, $microseconds);
        } elseif (is_string($value)) {
            try {
                $datetime = new \DateTime($value);
            } catch (\Throwable $e) {
                $exception = $e;
            }
        }

        if ($datetime === false) {
            throw new \InvalidArgumentException(sprintf('Could not convert %s to a date value', is_scalar($value) ? '"' . $value . '"' : gettype($value)), 0, $exception);
        }

        return $datetime;
    }

    private static function craftDateTime(int $seconds, $microseconds = 0): \DateTime
    {
        // @todo fix typing for $microseconds
        $datetime = new \DateTime();
        $datetime->setTimestamp($seconds);
        if ($microseconds > 0) {
            $datetime = \DateTime::createFromFormat('Y-m-d H:i:s.u', $datetime->format('Y-m-d H:i:s') . '.' . $microseconds);
        }

        return $datetime;
    }

    public function convertToDatabaseValue($value)
    {
        if ($value === null || $value instanceof UTCDateTime) {
            return $value;
        }

        $datetime = static::getDateTime($value);

        return new UTCDateTime((int) $datetime->format('Uv'));
    }

    public function convertToPHPValue($value)
    {
        if ($value === null) {
            return null;
        }

        return static::getDateTime($value);
    }

    public function closureToMongo(): string
    {
        return 'if ($value === null || $value instanceof \MongoDB\BSON\UTCDateTime) { $return = $value; } else { $datetime = \\' . get_class($this) . '::getDateTime($value); $return = new \MongoDB\BSON\UTCDateTime((int) $datetime->format(\'Uv\')); }';
    }

    public function closureToPHP(): string
    {
        return 'if ($value === null) { $return = null; } else { $return = \\' . get_class($this) . '::getDateTime($value); }';
    }
}
