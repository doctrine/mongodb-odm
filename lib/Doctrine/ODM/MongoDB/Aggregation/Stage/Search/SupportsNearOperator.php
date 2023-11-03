<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use GeoJson\Geometry\Point;
use MongoDB\BSON\UTCDateTime;

interface SupportsNearOperator
{
    /**
     * @param int|float|UTCDateTime|array|Point|null $origin
     * @param int|float|null                         $pivot
     */
    public function near($origin = null, $pivot = null, string ...$path): Near;
}
