<?php

declare(strict_types=1);

namespace Documentation\CustomMapping;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\UTCDateTime;
use RuntimeException;

use function gettype;
use function sprintf;

class DateTimeWithTimezoneType extends Type
{
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

    /** @return array{utc: UTCDateTime, tz: string} */
    public function convertToDatabaseValue($value): array
    {
        if (! $value instanceof DateTimeImmutable) {
            throw new RuntimeException(
                sprintf(
                    'Expected instance of \DateTimeImmutable, got %s',
                    gettype($value),
                ),
            );
        }

        return [
            'utc' => new UTCDateTime($value),
            'tz' => $value->getTimezone()->getName(),
        ];
    }
}
