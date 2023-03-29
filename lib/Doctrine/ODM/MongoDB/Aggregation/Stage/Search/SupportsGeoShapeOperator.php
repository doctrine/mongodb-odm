<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use GeoJson\Geometry\LineString;
use GeoJson\Geometry\MultiPolygon;
use GeoJson\Geometry\Point;
use GeoJson\Geometry\Polygon;

interface SupportsGeoShapeOperator
{
    /** @param LineString|Point|Polygon|MultiPolygon|array|null $geometry */
    public function geoShape($geometry = null, string $relation = '', string ...$path): GeoShape;
}
