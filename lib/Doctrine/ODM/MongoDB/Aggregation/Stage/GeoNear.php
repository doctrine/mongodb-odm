<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use GeoJson\Geometry\Point;

use function is_array;

/**
 * Fluent interface for adding a $geoNear stage to an aggregation pipeline.
 */
class GeoNear extends MatchStage
{
    /** @var string */
    private $distanceField;

    /** @var float */
    private $distanceMultiplier;

    /** @var string */
    private $includeLocs;

    /** @var float */
    private $maxDistance;

    /** @var float */
    private $minDistance;

    /** @var array */
    private $near;

    /** @var int */
    private $num;

    /** @var bool */
    private $spherical = false;

    /** @var bool */
    private $uniqueDocs;

    /**
     * @param float|array|Point $x
     * @param float             $y
     */
    public function __construct(Builder $builder, $x, $y = null)
    {
        parent::__construct($builder);

        $this->near($x, $y);
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression(): array
    {
        $geoNear = [
            'near' => $this->near,
            'spherical' => $this->spherical,
            'distanceField' => $this->distanceField,
            'query' => $this->query->getQuery(),
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
    public function distanceField(string $distanceField): self
    {
        $this->distanceField = $distanceField;

        return $this;
    }

    /**
     * The factor to multiply all distances returned by the query.
     */
    public function distanceMultiplier(float $distanceMultiplier): self
    {
        $this->distanceMultiplier = $distanceMultiplier;

        return $this;
    }

    /**
     * This specifies the output field that identifies the location used to calculate the distance.
     */
    public function includeLocs(string $includeLocs): self
    {
        $this->includeLocs = $includeLocs;

        return $this;
    }

    /**
     * The maximum number of documents to return.
     */
    public function limit(int $limit): self
    {
        return $this->num($limit);
    }

    /**
     * The maximum distance from the center point that the documents can be.
     */
    public function maxDistance(float $maxDistance): self
    {
        $this->maxDistance = $maxDistance;

        return $this;
    }

    /**
     * The minimum distance from the center point that the documents can be.
     */
    public function minDistance(float $minDistance): self
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
     * @param float|array|Point $x
     * @param float             $y
     */
    public function near($x, $y = null): self
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
    public function num(int $num): self
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Required if using a 2dsphere index. Determines how MongoDB calculates the distance.
     */
    public function spherical(bool $spherical = true): self
    {
        $this->spherical = $spherical;

        return $this;
    }

    /**
     * If this value is true, the query returns a matching document once, even if more than one of the document’s location fields match the query.
     */
    public function uniqueDocs(bool $uniqueDocs = true): self
    {
        $this->uniqueDocs = $uniqueDocs;

        return $this;
    }
}
