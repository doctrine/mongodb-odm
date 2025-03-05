<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use GeoJson\Geometry\Point;
use stdClass;

use function is_array;

/**
 * Fluent interface for adding a $geoNear stage to an aggregation pipeline.
 */
class GeoNear extends MatchStage
{
    private ?string $distanceField = null;

    private ?float $distanceMultiplier = null;

    private ?string $includeLocs = null;

    private ?float $maxDistance = null;

    private ?float $minDistance = null;

    /** @var array<string, mixed>|array{int|float, int|float} */
    private array $near;

    private ?int $num = null;

    private bool $spherical = false;

    private ?bool $uniqueDocs = null;

    /**
     * @param float|array<string, mixed>|Point $x
     * @param float                            $y
     */
    public function __construct(Builder $builder, $x, $y = null)
    {
        parent::__construct($builder);

        $this->near($x, $y);
    }

    public function getExpression(): array
    {
        $geoNear = [
            'near' => $this->near,
            'spherical' => $this->spherical,
            'distanceField' => $this->distanceField,
            'query' => $this->query->getQuery() ?: new stdClass(),
            'distanceMultiplier' => $this->distanceMultiplier,
            'includeLocs' => $this->includeLocs,
            'maxDistance' => $this->maxDistance,
            'minDistance' => $this->minDistance,
            'num' => $this->num,
            'uniqueDocs' => $this->uniqueDocs,
        ];

        foreach (['distanceMultiplier', 'includeLocs', 'maxDistance', 'minDistance', 'num', 'uniqueDocs'] as $option) {
            if ($geoNear[$option]) {
                continue;
            }

            unset($geoNear[$option]);
        }

        return ['$geoNear' => $geoNear];
    }

    /**
     * The output field that contains the calculated distance. To specify a field within an embedded document, use dot notation.
     */
    public function distanceField(string $distanceField): static
    {
        $this->distanceField = $distanceField;

        return $this;
    }

    /**
     * The factor to multiply all distances returned by the query.
     */
    public function distanceMultiplier(float $distanceMultiplier): static
    {
        $this->distanceMultiplier = $distanceMultiplier;

        return $this;
    }

    /**
     * This specifies the output field that identifies the location used to calculate the distance.
     */
    public function includeLocs(string $includeLocs): static
    {
        $this->includeLocs = $includeLocs;

        return $this;
    }

    /**
     * The maximum number of documents to return.
     */
    public function limit(int $limit): static
    {
        return $this->num($limit);
    }

    /**
     * The maximum distance from the center point that the documents can be.
     */
    public function maxDistance(float $maxDistance): static
    {
        $this->maxDistance = $maxDistance;

        return $this;
    }

    /**
     * The minimum distance from the center point that the documents can be.
     */
    public function minDistance(float $minDistance): static
    {
        $this->minDistance = $minDistance;

        return $this;
    }

    /**
     * The point for which to find the closest documents.
     *
     * A GeoJSON point may be provided as the first and only argument for
     * 2dsphere queries. This single parameter may be a GeoJSON point object or
     * an array corresponding to the point's JSON representation. If GeoJSON is
     * used, the "spherical" option will default to true.
     *
     * @param float|array<string, mixed>|Point $x
     * @param float                            $y
     */
    public function near($x, $y = null): static
    {
        if ($x instanceof Point) {
            $x = $x->jsonSerialize();
        }

        $this->near      = is_array($x) ? $x : [$x, $y];
        $this->spherical = is_array($x) && isset($x['type']);

        return $this;
    }

    /**
     * The maximum number of documents to return.
     */
    public function num(int $num): static
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Required if using a 2dsphere index. Determines how MongoDB calculates the distance.
     */
    public function spherical(bool $spherical = true): static
    {
        $this->spherical = $spherical;

        return $this;
    }

    /**
     * If this value is true, the query returns a matching document once, even if more than one of the document’s location fields match the query.
     */
    public function uniqueDocs(bool $uniqueDocs = true): static
    {
        $this->uniqueDocs = $uniqueDocs;

        return $this;
    }
}
