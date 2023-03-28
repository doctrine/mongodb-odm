<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;

use function sprintf;

class DateImmutableType extends DateType
{
    /** @return DateTimeImmutable */
    public static function getDateTime($value): DateTimeInterface
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

    /**
     * @param mixed $current
     *
     * @return DateTimeInterface
     */
    public function getNextVersion($current)
    {
        return new DateTimeImmutable();
    }
}
