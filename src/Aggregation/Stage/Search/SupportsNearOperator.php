<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use GeoJson\Geometry\Point;
use MongoDB\BSON\UTCDateTime;

interface SupportsNearOperator
{
    /** @param int|float|UTCDateTime|array<string, mixed>|Point|null $origin */
    public function near(int|float|UTCDateTime|array|Point|null $origin = null, int|float|null $pivot = null, string ...$path): Near;
}
