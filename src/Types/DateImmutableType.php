<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use DateTime;
use DateTimeImmutable;
use RuntimeException;

use function sprintf;

class DateImmutableType extends DateType
{
    public static function getDateTime(mixed $value): DateTimeImmutable
    {
        $datetime = parent::getDateTime($value);

        if ($datetime instanceof DateTimeImmutable) {
            return $datetime;
        }

        if ($datetime instanceof DateTime) {
            return DateTimeImmutable::createFromMutable($datetime);
        }

        throw new RuntimeException(sprintf(
            '%s::getDateTime has returned an unsupported implementation of DateTimeInterface: %s',
            parent::class,
            $datetime::class,
        ));
    }

    public function getNextVersion(mixed $current): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
