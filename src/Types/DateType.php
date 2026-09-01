<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use MongoDB\BSON\UTCDateTime;
use Throwable;

use function abs;
use function date_default_timezone_get;
use function gettype;
use function is_numeric;
use function is_scalar;
use function is_string;
use function round;
use function sprintf;
use function str_pad;

use const STR_PAD_LEFT;

/**
 * The Date type.
 */
class DateType extends Type implements Versionable
{
    /**
     * Converts a value to a DateTime.
     * Supports microseconds
     *
     * @param mixed $value \DateTimeInterface|\MongoDB\BSON\UTCDateTime|int|float
     *
     * @throws InvalidArgumentException If $value is invalid.
     */
    public static function getDateTime(mixed $value): DateTimeInterface
    {
        $datetime  = false;
        $exception = null;

        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if ($value instanceof UTCDateTime) {
            $datetime = $value->toDateTime();
            $datetime->setTimezone(new DateTimeZone(date_default_timezone_get()));
        } elseif (is_numeric($value)) {
            $value         = (float) $value;
            $seconds       = (int) $value;
            $microseconds  = abs(round($value - $seconds, 6));
            $microseconds *= 1_000_000;

            $datetime = static::craftDateTime($seconds, (int) $microseconds);
        } elseif (is_string($value)) {
            try {
                $datetime = new DateTime($value);
            } catch (Throwable $e) {
                $exception = $e;
            }
        }

        if ($datetime === false) {
            throw new InvalidArgumentException(sprintf('Could not convert %s to a date value', is_scalar($value) ? '"' . $value . '"' : gettype($value)), 0, $exception);
        }

        return $datetime;
    }

    private static function craftDateTime(int $seconds, int $microseconds = 0): DateTime|false
    {
        $datetime = new DateTime();
        $datetime->setTimestamp($seconds);
        if ($microseconds > 0) {
            $datetime = DateTime::createFromFormat('Y-m-d H:i:s.u', $datetime->format('Y-m-d H:i:s') . '.' . str_pad((string) $microseconds, 6, '0', STR_PAD_LEFT));
        }

        return $datetime;
    }

    public function convertToDatabaseValue(mixed $value): ?UTCDateTime
    {
        if ($value === null || $value instanceof UTCDateTime) {
            return $value;
        }

        $datetime = static::getDateTime($value);

        return new UTCDateTime($datetime);
    }

    public function convertToPHPValue(mixed $value): ?DateTimeInterface
    {
        if ($value === null) {
            return null;
        }

        return static::getDateTime($value);
    }

    public function closureToPHP(): string
    {
        return 'if ($value === null) { $return = null; } else { $return = \\' . static::class . '::getDateTime($value); }';
    }

    public function getNextVersion(mixed $current): DateTimeInterface
    {
        return new DateTime();
    }
}
