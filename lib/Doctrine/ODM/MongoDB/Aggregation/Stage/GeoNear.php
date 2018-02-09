<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use GeoJson\Geometry\Point;
use function is_array;

/**
 * Fluent interface for adding a $geoNear stage to an aggregation pipeline.
 *
 */
class GeoNear extends Match
{
    /**
     * @var string
     */
    private $distanceField;

    /**
     * @var float
     */
    private $distanceMultiplier;

    /**
     * @var string
     */
    private $includeLocs;

    /**
     * @var float
     */
    private $maxDistance;

    /**
     * @var float
     */
    private $minDistance;

    /**
     * @var array
     */
    private $near;

    /**
     * @var int
     */
    private $num;

    /**
     * @var bool
     */
    private $spherical = false;

    /**
     * @var bool
     */
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
    public function getExpression()
    {
        $geoNear = [
            'near' => $this->near,
            'spherical' => $this->spherical,
            'distanceField' => $this->distanceField,
            'query' => $this->query->getQuery(),
        ];

        foreach (['distanceMultiplier', 'includeLocs', 'maxDistance', 'minDistance', 'num', 'uniqueDocs'] as $option) {
            if (! $this->$option) {
                continue;
            }

            $geoNear[$option] = $this->$option;
        }

        return ['$geoNear' => $geoNear];
    }

    /**
     * The output field that contains the calculated distance. To specify a field within an embedded document, use dot notation.
     *
     * @param string $distanceField
     * @return $this
     */
    public function distanceField($distanceField)
    {
        $this->distanceField = (string) $distanceField;

        return $this;
    }

    /**
     * The factor to multiply all distances returned by the query.
     *
     * @param float $distanceMultiplier
     * @return $this
     */
    public function distanceMultiplier($distanceMultiplier)
    {
        $this->distanceMultiplier = (float) $distanceMultiplier;

        return $this;
    }

    /**
     * This specifies the output field that identifies the location used to calculate the distance.
     *
     * @param string $includeLocs
     * @return $this
     */
    public function includeLocs($includeLocs)
    {
        $this->includeLocs = (string) $includeLocs;

        return $this;
    }

    /**
     * The maximum number of documents to return.
     *
     * @param int $limit
     * @return $this
     */
    public function limit($limit)
    {
        return $this->num($limit);
    }

    /**
     * The maximum distance from the center point that the documents can be.
     *
     * @param float $maxDistance
     * @return $this
     */
    public function maxDistance($maxDistance)
    {
        $this->maxDistance = (float) $maxDistance;

        return $this;
    }

    /**
     * The minimum distance from the center point that the documents can be.
     *
     * @param float $minDistance
     * @return $this
     *
     */
    public function minDistance($minDistance)
    {
        $this->minDistance = (float) $minDistance;

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
     * @return $this
     */
    public function near($x, $y = null)
    {
        if ($x instanceof Point) {
            $x = $x->jsonSerialize();
        }

        $this->near = is_array($x) ? $x : [$x, $y];
        $this->spherical = is_array($x) && isset($x['type']);

        return $this;
    }

    /**
     * The maximum number of documents to return.
     *
     * @param int $num
     * @return $this
     */
    public function num($num)
    {
        $this->num = (int) $num;

        return $this;
    }

    /**
     * Required if using a 2dsphere index. Determines how MongoDB calculates the distance.
     *
     * @param bool $spherical
     * @return $this
     */
    public function spherical($spherical = true)
    {
        $this->spherical = (bool) $spherical;

        return $this;
    }

    /**
     * If this value is true, the query returns a matching document once, even if more than one of the document’s location fields match the query.
     *
     * @param bool $uniqueDocs
     * @return $this
     */
    public function uniqueDocs($uniqueDocs = true)
    {
        $this->uniqueDocs = (bool) $uniqueDocs;

        return $this;
    }
}
