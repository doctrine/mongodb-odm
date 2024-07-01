<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;
use GeoJson\Geometry\Geometry;
use GeoJson\Geometry\MultiPolygon;
use GeoJson\Geometry\Point;
use GeoJson\Geometry\Polygon;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/geoWithin/
 */
class GeoWithin extends AbstractSearchOperator implements ScoredSearchOperator
{
    use ScoredSearchOperatorTrait;

    /** @var list<string> */
    private array $path      = [];
    private string $relation = '';
    private ?object $box     = null;
    private ?object $circle  = null;

    private array|MultiPolygon|Polygon|null $geometry = null;

    public function __construct(Search $search, string ...$path)
    {
        parent::__construct($search);

        $this->path(...$path);
    }

    public function path(string ...$path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param array|Point $bottomLeft
     * @param array|Point $topRight
     */
    public function box($bottomLeft, $topRight): static
    {
        $this->box = (object) [
            'bottomLeft' => $this->convertGeometry($bottomLeft),
            'topRight' => $this->convertGeometry($topRight),
        ];

        return $this;
    }

    /**
     * @param array|Point $center
     * @param int|float   $radius
     */
    public function circle($center, $radius): static
    {
        $this->circle = (object) [
            'center' => $this->convertGeometry($center),
            'radius' => $radius,
        ];

        return $this;
    }

    /** @param Polygon|MultiPolygon|array $geometry */
    public function geometry($geometry): static
    {
        $this->geometry = $geometry;

        return $this;
    }

    public function getOperatorName(): string
    {
        return 'geoWithin';
    }

    public function getOperatorParams(): object
    {
        $params = (object) ['path' => $this->path];

        if ($this->box) {
            $params->box = $this->box;
        }

        if ($this->circle) {
            $params->circle = $this->circle;
        }

        if ($this->geometry) {
            $params->geometry = $this->convertGeometry($this->geometry);
        }

        return $this->appendScore($params);
    }

    /** @param array|Geometry $geometry */
    private function convertGeometry($geometry): array
    {
        if (! $geometry instanceof Geometry) {
            return $geometry;
        }

        return $geometry->jsonSerialize();
    }
}
