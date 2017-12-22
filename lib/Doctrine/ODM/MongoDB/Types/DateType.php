<?php

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Date type.
 *
 * @since       1.0
 */
class DateType extends Type
{
    /**
     * Converts a value to a DateTime.
     * Supports microseconds
     *
     * @throws InvalidArgumentException if $value is invalid
     * @param  mixed $value \DateTimeInterface|\MongoDB\BSON\UTCDateTime|int|float
     * @return \DateTime
     */
    public static function getDateTime($value)
    {
        $datetime = false;
        $exception = null;

        if ($value instanceof \DateTimeInterface) {
            return $value;
        } elseif ($value instanceof \MongoDB\BSON\UTCDateTime) {
            $datetime = $value->toDateTime();
            $datetime->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        } elseif (is_numeric($value)) {
            $seconds = $value;
            $microseconds = 0;

            if (false !== strpos($value, '.')) {
                list($seconds, $microseconds) = explode('.', $value);
                $microseconds = str_pad($microseconds, 6, '0'); // ensure microseconds
            }

            $datetime = static::craftDateTime($seconds, $microseconds);
        } elseif (is_string($value)) {
            try {
                $datetime = new \DateTime($value);
            } catch (\Exception $e) {
                $exception = $e;
            }
        }

        if ($datetime === false) {
            throw new \InvalidArgumentException(sprintf('Could not convert %s to a date value', is_scalar($value) ? '"'.$value.'"' : gettype($value)), 0, $exception);
        }

        return $datetime;
    }

    private static function craftDateTime($seconds, $microseconds = 0)
    {
        $datetime = new \DateTime();
        $datetime->setTimestamp($seconds);
        if ($microseconds > 0) {
            $datetime = \DateTime::createFromFormat('Y-m-d H:i:s.u', $datetime->format('Y-m-d H:i:s') . '.' . $microseconds);
        }

        return $datetime;
    }

    public function convertToDatabaseValue($value)
    {
        if ($value === null || $value instanceof \MongoDB\BSON\UTCDateTime) {
            return $value;
        }

        $datetime = static::getDateTime($value);

        return new \MongoDB\BSON\UTCDateTime((int) $datetime->format('Uv'));
    }

    public function convertToPHPValue($value)
    {
        if ($value === null) {
            return null;
        }

        return static::getDateTime($value);
    }

    public function closureToMongo()
    {
        return 'if ($value === null || $value instanceof \MongoDB\BSON\UTCDateTime) { $return = $value; } else { $datetime = \\'.get_class($this).'::getDateTime($value); $return = new \MongoDB\BSON\UTCDateTime((int) $datetime->format(\'Uv\')); }';
    }

    public function closureToPHP()
    {
        return 'if ($value === null) { $return = null; } else { $return = \\'.get_class($this).'::getDateTime($value); }';
    }
}
