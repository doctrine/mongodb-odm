<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Mapping\TimeSeries;

enum Granularity: string
{
    case Seconds = 'seconds';
    case Minutes = 'minutes';
    case Hours   = 'hours';
}
