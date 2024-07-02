<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;
use GeoJson\Geometry\Geometry;
use GeoJson\Geometry\LineString;
use GeoJson\Geometry\MultiPolygon;
use GeoJson\Geometry\Point;
use GeoJson\Geometry\Polygon;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/geoShape/
 */
class GeoShape extends AbstractSearchOperator implements ScoredSearchOperator
{
    use ScoredSearchOperatorTrait;

    /** @var list<string> */
    private array $path      = [];
    private string $relation = '';

    private LineString|Point|Polygon|MultiPolygon|array|null $geometry = null;

    /** @param LineString|Point|Polygon|MultiPolygon|array|null $geometry */
    public function __construct(Search $search, $geometry = null, string $relation = '', string ...$path)
    {
        parent::__construct($search);

        $this
            ->geometry($geometry)
            ->relation($relation)
            ->path(...$path);
    }

    public function path(string ...$path): static
    {
        $this->path = $path;

        return $this;
    }

    public function relation(string $relation): static
    {
        $this->relation = $relation;

        return $this;
    }

    /** @param LineString|Point|Polygon|MultiPolygon|array|null $geometry */
    public function geometry($geometry): static
    {
        $this->geometry = $geometry;

        return $this;
    }

    public function getOperatorName(): string
    {
        return 'geoShape';
    }

    public function getOperatorParams(): object
    {
        $params = (object) [
            'path' => $this->path,
            'relation' => $this->relation,
            'geometry' => $this->geometry instanceof Geometry
                ? $this->geometry->jsonSerialize()
                : $this->geometry,
        ];

        return $this->appendScore($params);
    }
}
