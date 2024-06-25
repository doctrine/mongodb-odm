<?php

declare(strict_types=1);

namespace Documentation\CustomMapping;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ODM\MongoDB\Types\ClosureToPHP;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\UTCDateTime;
use RuntimeException;

class DateTimeWithTimezoneType extends Type
{
    // This trait provides default closureToPHP used during data hydration
    use ClosureToPHP;

    /** @param array{utc: UTCDateTime, tz: string} $value */
    public function convertToPHPValue($value): DateTimeImmutable
    {
        if (! isset($value['utc'], $value['tz'])) {
            throw new RuntimeException('Database value cannot be converted to date with timezone. Expected array with "utc" and "tz" keys.');
        }

        $timeZone = new DateTimeZone($value['tz']);
        $dateTime = $value['utc']
            ->toDateTime()
            ->setTimeZone($timeZone);

        return DateTimeImmutable::createFromMutable($dateTime);
    }

    /**
     * @param DateTimeInterface $value
     *
     * @return array{utc: UTCDateTime, tz: string}
     */
    public function convertToDatabaseValue($value): array
    {
        return [
            'utc' => new UTCDateTime($value),
            'tz' => $value->getTimezone()->getName(),
        ];
    }
}
