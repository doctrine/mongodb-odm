<?php

declare(strict_types=1);

namespace Documents\TimeSeries;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\TimeSeries\Granularity;

#[ODM\Document]
#[ODM\TimeSeries(timeField: 'time', metaField: 'metadata', granularity: Granularity::Seconds, expireAfterSeconds: 86400)]
class TimeSeriesDocument
{
    #[ODM\Id]
    public ?string $id;

    #[ODM\Field]
    public DateTime $time;

    #[ODM\Field]
    public string $metadata;

    #[ODM\Field]
    public int $value;
}
