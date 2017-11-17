<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use GeoJson\Geometry\Point;

/**
 * Fluent interface for adding a $geoNear stage to an aggregation pipeline.
 *
 * @author alcaeus <alcaeus@alcaeus.org>
 * @since 1.2
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
     * @var integer
     */
    private $num;

    /**
     * @var boolean
     */
    private $spherical = false;

    /**
     * @var boolean
     */
    private $uniqueDocs;

    /**
     * @param Builder $builder
     * @param float|array|Point $x
     * @param float $y
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
            'query' => $this->query->getQuery()
        ];

        foreach (['distanceMultiplier', 'includeLocs', 'maxDistance', 'minDistance', 'num', 'uniqueDocs'] as $option) {
            if ( ! $this->$option) {
                continue;
            }

            $geoNear[$option] = $this->$option;
        }

        return [
            '$geoNear' => $geoNear
        ];
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
     * @param integer $limit
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
     * @since 1.3
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
     * @param float $y
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
     * @param integer $num
     * @return $this
     */
    public function num($num)
    {
        $this->num = (integer) $num;

        return $this;
    }

    /**
     * Required if using a 2dsphere index. Determines how MongoDB calculates the distance.
     *
     * @param boolean $spherical
     * @return $this
     */
    public function spherical($spherical = true)
    {
        $this->spherical = (boolean) $spherical;

        return $this;
    }

    /**
     * If this value is true, the query returns a matching document once, even if more than one of the document’s location fields match the query.
     *
     * @param boolean $uniqueDocs
     * @return $this
     */
    public function uniqueDocs($uniqueDocs = true)
    {
        $this->uniqueDocs = (boolean) $uniqueDocs;

        return $this;
    }
}
