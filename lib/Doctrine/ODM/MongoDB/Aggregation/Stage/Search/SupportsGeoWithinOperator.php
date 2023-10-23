<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

interface SupportsGeoWithinOperator
{
    public function geoWithin(string ...$path): GeoWithin;
}
