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

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use GeoJson\Geometry\Geometry;
use GeoJson\Geometry\Point;

/**
 * Query expression builder for ODM.
 *
 * @since       1.0
 */
class Expr
{
    /**
     * The query criteria array.
     *
     * @var array
     */
    private $query = [];

    /**
     * The "new object" array containing either a full document or a number of
     * atomic update operators.
     *
     * @see docs.mongodb.org/manual/reference/method/db.collection.update/#update-parameter
     * @var array
     */
    private $newObj = [];

    /**
     * The current field we are operating on.
     *
     * @var string
     */
    private $currentField;

    /**
     * The DocumentManager instance for this query
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The ClassMetadata instance for the document being queried
     *
     * @var ClassMetadata
     */
    private $class;

    /**
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * Add one or more $and clauses to the current query.
     *
     * @see Builder::addAnd()
     * @see http://docs.mongodb.org/manual/reference/operator/and/
     * @param array|Expr $expression
     * @return $this
     */
    public function addAnd($expression /*, $expression2, ... */)
    {
        if (! isset($this->query['$and'])) {
            $this->query['$and'] = [];
        }

        $this->query['$and'] = array_merge(
            $this->query['$and'],
            array_map(
                function ($expression) {
                    return $expression instanceof Expr ? $expression->getQuery() : $expression;
                },
                func_get_args()
            )
        );

        return $this;
    }

    /**
     * Append multiple values to the current array field only if they do not
     * already exist in the array.
     *
     * If the field does not exist, it will be set to an array containing the
     * unique values in the argument. If the field is not an array, the query
     * will yield an error.
     *
     * @deprecated 1.1 Use {@link Expr::addToSet()} with {@link Expr::each()}; Will be removed in 2.0
     * @see Builder::addManyToSet()
     * @see http://docs.mongodb.org/manual/reference/operator/addToSet/
     * @see http://docs.mongodb.org/manual/reference/operator/each/
     * @param array $values
     * @return $this
     */
    public function addManyToSet(array $values)
    {
        $this->requiresCurrentField();
        $this->newObj['$addToSet'][$this->currentField] = ['$each' => $values];
        return $this;
    }

    /**
     * Add one or more $nor clauses to the current query.
     *
     * @see Builder::addNor()
     * @see http://docs.mongodb.org/manual/reference/operator/nor/
     * @param array|Expr $expression
     * @return $this
     */
    public function addNor($expression /* , $expression2, ... */)
    {
        if (! isset($this->query['$nor'])) {
            $this->query['$nor'] = [];
        }

        $this->query['$nor'] = array_merge(
            $this->query['$nor'],
            array_map(function ($expression) { return $expression instanceof Expr ? $expression->getQuery() : $expression; }, func_get_args())
        );

        return $this;
    }

    /**
     * Add one or more $or clauses to the current query.
     *
     * @see Builder::addOr()
     * @see http://docs.mongodb.org/manual/reference/operator/or/
     * @param array|Expr $expression
     * @return $this
     */
    public function addOr($expression /* , $expression2, ... */)
    {
        if (! isset($this->query['$or'])) {
            $this->query['$or'] = [];
        }

        $this->query['$or'] = array_merge(
            $this->query['$or'],
            array_map(function ($expression) { return $expression instanceof Expr ? $expression->getQuery() : $expression; }, func_get_args())
        );

        return $this;
    }

    /**
     * Append one or more values to the current array field only if they do not
     * already exist in the array.
     *
     * If the field does not exist, it will be set to an array containing the
     * unique value(s) in the argument. If the field is not an array, the query
     * will yield an error.
     *
     * Multiple values may be specified by provided an Expr object and using
     * {@link Expr::each()}.
     *
     * @see Builder::addToSet()
     * @see http://docs.mongodb.org/manual/reference/operator/addToSet/
     * @see http://docs.mongodb.org/manual/reference/operator/each/
     * @param mixed|Expr $valueOrExpression
     * @return $this
     */
    public function addToSet($valueOrExpression)
    {
        if ($valueOrExpression instanceof Expr) {
            $valueOrExpression = $valueOrExpression->getQuery();
        }

        $this->requiresCurrentField();
        $this->newObj['$addToSet'][$this->currentField] = $valueOrExpression;
        return $this;
    }

    /**
     * Specify $all criteria for the current field.
     *
     * @see Builder::all()
     * @see http://docs.mongodb.org/manual/reference/operator/all/
     * @param array $values
     * @return $this
     */
    public function all(array $values)
    {
        return $this->operator('$all', (array) $values);
    }

    /**
     * Apply a bitwise operation on the current field
     *
     * @see http://docs.mongodb.org/manual/reference/operator/update/bit/
     * @param string $operator
     * @param int $value
     * @return $this
     */
    protected function bit($operator, $value)
    {
        $this->requiresCurrentField();
        $this->newObj['$bit'][$this->currentField][$operator] = $value;
        return $this;
    }

    /**
     * Apply a bitwise and operation on the current field.
     *
     * @see Builder::bitAnd()
     * @see http://docs.mongodb.org/manual/reference/operator/update/bit/
     * @param int $value
     * @return $this
     */
    public function bitAnd($value)
    {
        return $this->bit('and', $value);
    }

    /**
     * Apply a bitwise or operation on the current field.
     *
     * @see Builder::bitOr()
     * @see http://docs.mongodb.org/manual/reference/operator/update/bit/
     * @param int $value
     * @return $this
     */
    public function bitOr($value)
    {
        return $this->bit('or', $value);
    }

    /**
     * Matches documents where all of the bit positions given by the query are
     * clear.
     *
     * @see Builder::bitsAllClear()
     * @see https://docs.mongodb.org/manual/reference/operator/query/bitsAllClear/
     * @param int|array|\MongoBinData $value
     * @return $this
     */
    public function bitsAllClear($value)
    {
        $this->requiresCurrentField();
        return $this->operator('$bitsAllClear', $value);
    }

    /**
     * Matches documents where all of the bit positions given by the query are
     * set.
     *
     * @see Builder::bitsAllSet()
     * @see https://docs.mongodb.org/manual/reference/operator/query/bitsAllSet/
     * @param int|array|\MongoBinData $value
     * @return $this
     */
    public function bitsAllSet($value)
    {
        $this->requiresCurrentField();
        return $this->operator('$bitsAllSet', $value);
    }

    /**
     * Matches documents where any of the bit positions given by the query are
     * clear.
     *
     * @see Builder::bitsAnyClear()
     * @see https://docs.mongodb.org/manual/reference/operator/query/bitsAnyClear/
     * @param int|array|\MongoBinData $value
     * @return $this
     */
    public function bitsAnyClear($value)
    {
        $this->requiresCurrentField();
        return $this->operator('$bitsAnyClear', $value);
    }

    /**
     * Matches documents where any of the bit positions given by the query are
     * set.
     *
     * @see Builder::bitsAnySet()
     * @see https://docs.mongodb.org/manual/reference/operator/query/bitsAnySet/
     * @param int|array|\MongoBinData $value
     * @return $this
     */
    public function bitsAnySet($value)
    {
        $this->requiresCurrentField();
        return $this->operator('$bitsAnySet', $value);
    }

    /**
     * Apply a bitwise xor operation on the current field.
     *
     * @see Builder::bitXor()
     * @see http://docs.mongodb.org/manual/reference/operator/update/bit/
     * @param int $value
     * @return $this
     */
    public function bitXor($value)
    {
        return $this->bit('xor', $value);
    }

    /**
     * A boolean flag to enable or disable case sensitive search for $text
     * criteria.
     *
     * This method must be called after text().
     *
     * @see Builder::caseSensitive()
     * @see http://docs.mongodb.org/manual/reference/operator/text/
     * @param bool $caseSensitive
     * @return $this
     * @throws \BadMethodCallException if the query does not already have $text criteria
     *
     * @since 1.3
     */
    public function caseSensitive($caseSensitive)
    {
        if ( ! isset($this->query['$text'])) {
            throw new \BadMethodCallException('This method requires a $text operator (call text() first)');
        }

        // Remove caseSensitive option to keep support for older database versions
        if ($caseSensitive) {
            $this->query['$text']['$caseSensitive'] = true;
        } elseif (isset($this->query['$text']['$caseSensitive'])) {
            unset($this->query['$text']['$caseSensitive']);
        }

        return $this;
    }

    /**
     * Associates a comment to any expression taking a query predicate.
     *
     * @see Builder::comment()
     * @see http://docs.mongodb.org/manual/reference/operator/query/comment/
     * @param string $comment
     * @return $this
     */
    public function comment($comment)
    {
        $this->query['$comment'] = $comment;
        return $this;
    }

    /**
     * Sets the value of the current field to the current date, either as a date or a timestamp.
     *
     * @see Builder::currentDate()
     * @see http://docs.mongodb.org/manual/reference/operator/update/currentDate/
     * @param string $type
     * @return $this
     * @throws \InvalidArgumentException if an invalid type is given
     */
    public function currentDate($type = 'date')
    {
        if (! in_array($type, ['date', 'timestamp'])) {
            throw new \InvalidArgumentException('Type for currentDate operator must be date or timestamp.');
        }

        $this->requiresCurrentField();
        $this->newObj['$currentDate'][$this->currentField]['$type'] = $type;
        return $this;
    }

    /**
     * A boolean flag to enable or disable diacritic sensitive search for $text
     * criteria.
     *
     * This method must be called after text().
     *
     * @see Builder::diacriticSensitive()
     * @see http://docs.mongodb.org/manual/reference/operator/text/
     * @param bool $diacriticSensitive
     * @return $this
     * @throws \BadMethodCallException if the query does not already have $text criteria
     *
     * @since 1.3
     */
    public function diacriticSensitive($diacriticSensitive)
    {
        if ( ! isset($this->query['$text'])) {
            throw new \BadMethodCallException('This method requires a $text operator (call text() first)');
        }

        // Remove diacriticSensitive option to keep support for older database versions
        if ($diacriticSensitive) {
            $this->query['$text']['$diacriticSensitive'] = true;
        } elseif (isset($this->query['$text']['$diacriticSensitive'])) {
            unset($this->query['$text']['$diacriticSensitive']);
        }

        return $this;
    }

    /**
     * Add $each criteria to the expression for a $push operation.
     *
     * @see Expr::push()
     * @see http://docs.mongodb.org/manual/reference/operator/each/
     * @param array $values
     * @return $this
     */
    public function each(array $values)
    {
        return $this->operator('$each', $values);
    }

    /**
     * Specify $elemMatch criteria for the current field.
     *
     * @see Builder::elemMatch()
     * @see http://docs.mongodb.org/manual/reference/operator/elemMatch/
     * @param array|Expr $expression
     * @return $this
     */
    public function elemMatch($expression)
    {
        return $this->operator('$elemMatch', $expression instanceof Expr ? $expression->getQuery() : $expression);
    }

    /**
     * Specify an equality match for the current field.
     *
     * @see Builder::equals()
     * @param mixed $value
     * @return $this
     */
    public function equals($value)
    {
        if ($this->currentField) {
            $this->query[$this->currentField] = $value;
        } else {
            $this->query = $value;
        }
        return $this;
    }

    /**
     * Specify $exists criteria for the current field.
     *
     * @see Builder::exists()
     * @see http://docs.mongodb.org/manual/reference/operator/exists/
     * @param boolean $bool
     * @return $this
     */
    public function exists($bool)
    {
        return $this->operator('$exists', (boolean) $bool);
    }

    /**
     * Set the current field for building the expression.
     *
     * @see Builder::field()
     * @param string $field
     * @return $this
     */
    public function field($field)
    {
        $this->currentField = (string) $field;
        return $this;
    }

    /**
     * Add $geoIntersects criteria with a GeoJSON geometry to the expression.
     *
     * The geometry parameter GeoJSON object or an array corresponding to the
     * geometry's JSON representation.
     *
     * @see Builder::geoIntersects()
     * @see http://docs.mongodb.org/manual/reference/operator/geoIntersects/
     * @param array|Geometry $geometry
     * @return $this
     */
    public function geoIntersects($geometry)
    {
        if ($geometry instanceof Geometry) {
            $geometry = $geometry->jsonSerialize();
        }

        return $this->operator('$geoIntersects', ['$geometry' => $geometry]);
    }

    /**
     * Add $geoWithin criteria with a GeoJSON geometry to the expression.
     *
     * The geometry parameter GeoJSON object or an array corresponding to the
     * geometry's JSON representation.
     *
     * @see Builder::geoWithin()
     * @see http://docs.mongodb.org/manual/reference/operator/geoIntersects/
     * @param array|Geometry $geometry
     * @return $this
     */
    public function geoWithin($geometry)
    {
        if ($geometry instanceof Geometry) {
            $geometry = $geometry->jsonSerialize();
        }

        return $this->operator('$geoWithin', ['$geometry' => $geometry]);
    }

    /**
     * Add $geoWithin criteria with a $box shape to the expression.
     *
     * A rectangular polygon will be constructed from a pair of coordinates
     * corresponding to the bottom left and top right corners.
     *
     * Note: the $box operator only supports legacy coordinate pairs and 2d
     * indexes. This cannot be used with 2dsphere indexes and GeoJSON shapes.
     *
     * @see Builder::geoWithinBox()
     * @see http://docs.mongodb.org/manual/reference/operator/box/
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @return $this
     */
    public function geoWithinBox($x1, $y1, $x2, $y2)
    {
        $shape = ['$box' => [[$x1, $y1], [$x2, $y2]]];

        return $this->operator('$geoWithin', $shape);
    }

    /**
     * Add $geoWithin criteria with a $center shape to the expression.
     *
     * Note: the $center operator only supports legacy coordinate pairs and 2d
     * indexes. This cannot be used with 2dsphere indexes and GeoJSON shapes.
     *
     * @see Builider::geoWithinCenter()
     * @see http://docs.mongodb.org/manual/reference/operator/center/
     * @param float $x
     * @param float $y
     * @param float $radius
     * @return $this
     */
    public function geoWithinCenter($x, $y, $radius)
    {
        $shape = ['$center' => [[$x, $y], $radius]];

        return $this->operator('$geoWithin', $shape);
    }

    /**
     * Add $geoWithin criteria with a $centerSphere shape to the expression.
     *
     * Note: the $centerSphere operator supports both 2d and 2dsphere indexes.
     *
     * @see Builder::geoWithinCenterSphere()
     * @see http://docs.mongodb.org/manual/reference/operator/centerSphere/
     * @param float $x
     * @param float $y
     * @param float $radius
     * @return $this
     */
    public function geoWithinCenterSphere($x, $y, $radius)
    {
        $shape = ['$centerSphere' => [[$x, $y], $radius]];

        return $this->operator('$geoWithin', $shape);
    }

    /**
     * Add $geoWithin criteria with a $polygon shape to the expression.
     *
     * Point coordinates are in x, y order (easting, northing for projected
     * coordinates, longitude, latitude for geographic coordinates).
     *
     * The last point coordinate is implicitly connected with the first.
     *
     * Note: the $polygon operator only supports legacy coordinate pairs and 2d
     * indexes. This cannot be used with 2dsphere indexes and GeoJSON shapes.
     *
     * @see Builder::geoWithinPolygon()
     * @see http://docs.mongodb.org/manual/reference/operator/polygon/
     * @param array $point,... Three or more point coordinate tuples
     * @return $this
     * @throws \InvalidArgumentException if less than three points are given
     */
    public function geoWithinPolygon(/* array($x1, $y1), ... */)
    {
        if (func_num_args() < 3) {
            throw new \InvalidArgumentException('Polygon must be defined by three or more points.');
        }

        $shape = ['$polygon' => func_get_args()];

        return $this->operator('$geoWithin', $shape);
    }

    /**
     * Return the current field.
     *
     * @return string
     */
    public function getCurrentField()
    {
        return $this->currentField;
    }

    /**
     * Gets prepared newObj part of expression.
     *
     * @return array
     */
    public function getNewObj()
    {
        return $this->dm->getUnitOfWork()
            ->getDocumentPersister($this->class->name)
            ->prepareQueryOrNewObj($this->newObj, true);
    }

    /**
     * Gets prepared query part of expression.
     *
     * @return array
     */
    public function getQuery()
    {
        return $this->dm->getUnitOfWork()
            ->getDocumentPersister($this->class->name)
            ->prepareQueryOrNewObj($this->query);
    }

    /**
     * Specify $gt criteria for the current field.
     *
     * @see Builder::gt()
     * @see http://docs.mongodb.org/manual/reference/operator/gt/
     * @param mixed $value
     * @return $this
     */
    public function gt($value)
    {
        return $this->operator('$gt', $value);
    }

    /**
     * Specify $gte criteria for the current field.
     *
     * @see Builder::gte()
     * @see http://docs.mongodb.org/manual/reference/operator/gte/
     * @param mixed $value
     * @return $this
     */
    public function gte($value)
    {
        return $this->operator('$gte', $value);
    }

    /**
     * Specify $in criteria for the current field.
     *
     * @see Builder::in()
     * @see http://docs.mongodb.org/manual/reference/operator/in/
     * @param array $values
     * @return $this
     */
    public function in(array $values)
    {
        return $this->operator('$in', array_values($values));
    }

    /**
     * Increment the current field.
     *
     * If the field does not exist, it will be set to this value.
     *
     * @see Builder::inc()
     * @see http://docs.mongodb.org/manual/reference/operator/inc/
     * @param float|integer $value
     * @return $this
     */
    public function inc($value)
    {
        $this->requiresCurrentField();
        $this->newObj['$inc'][$this->currentField] = $value;
        return $this;
    }

    /**
     * Checks that the current field includes a reference to the supplied document.
     *
     * @param object $document
     * @return Expr
     */
    public function includesReferenceTo($document)
    {
        if ($this->currentField) {
            $mapping = $this->getReferenceMapping();
            $reference = $this->dm->createReference($document, $mapping);
            $storeAs = array_key_exists('storeAs', $mapping) ? $mapping['storeAs'] : null;

            switch ($storeAs) {
                case ClassMetadataInfo::REFERENCE_STORE_AS_ID:
                    $this->query[$mapping['name']] = $reference;
                    return $this;
                    break;

                case ClassMetadataInfo::REFERENCE_STORE_AS_REF:
                    $keys = ['id' => true];
                    break;

                case ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF:
                case ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF_WITH_DB:
                    $keys = ['$ref' => true, '$id' => true, '$db' => true];

                    if ($storeAs === ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF) {
                        unset($keys['$db']);
                    }

                    if (isset($mapping['targetDocument'])) {
                        unset($keys['$ref'], $keys['$db']);
                    }
                    break;

                default:
                    throw new \InvalidArgumentException("Reference type {$storeAs} is invalid.");
            }

            foreach ($keys as $key => $value) {
                $this->query[$mapping['name']]['$elemMatch'][$key] = $reference[$key];
            }
        } else {
            @trigger_error('Calling ' . __METHOD__ . ' without a current field set will no longer be possible in ODM 2.0.', E_USER_DEPRECATED);

            $this->query['$elemMatch'] = $this->dm->createDBRef($document);
        }

        return $this;
    }

    /**
     * Set the $language option for $text criteria.
     *
     * This method must be called after text().
     *
     * @see Builder::language()
     * @see http://docs.mongodb.org/manual/reference/operator/text/
     * @param string $language
     * @return $this
     * @throws \BadMethodCallException if the query does not already have $text criteria
     */
    public function language($language)
    {
        if ( ! isset($this->query['$text'])) {
            throw new \BadMethodCallException('This method requires a $text operator (call text() first)');
        }

        $this->query['$text']['$language'] = (string) $language;

        return $this;
    }

    /**
     * Specify $lt criteria for the current field.
     *
     * @see Builder::lte()
     * @see http://docs.mongodb.org/manual/reference/operator/lte/
     * @param mixed $value
     * @return $this
     */
    public function lt($value)
    {
        return $this->operator('$lt', $value);
    }

    /**
     * Specify $lte criteria for the current field.
     *
     * @see Builder::lte()
     * @see http://docs.mongodb.org/manual/reference/operator/lte/
     * @param mixed $value
     * @return $this
     */
    public function lte($value)
    {
        return $this->operator('$lte', $value);
    }

    /**
     * Updates the value of the field to a specified value if the specified value is greater than the current value of the field.
     *
     * @see Builder::max()
     * @see http://docs.mongodb.org/manual/reference/operator/update/max/
     * @param mixed $value
     * @return $this
     */
    public function max($value)
    {
        $this->requiresCurrentField();
        $this->newObj['$max'][$this->currentField] = $value;
        return $this;
    }

    /**
     * Set the $maxDistance option for $near or $nearSphere criteria.
     *
     * This method must be called after near() or nearSphere(), since placement
     * of the $maxDistance option depends on whether a GeoJSON point or legacy
     * coordinates were provided for $near/$nearSphere.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/maxDistance/
     * @param float $maxDistance
     * @return $this
     * @throws \BadMethodCallException if the query does not already have $near or $nearSphere criteria
     */
    public function maxDistance($maxDistance)
    {
        if ($this->currentField) {
            $query = &$this->query[$this->currentField];
        } else {
            $query = &$this->query;
        }

        if ( ! isset($query['$near']) && ! isset($query['$nearSphere'])) {
            throw new \BadMethodCallException(
                'This method requires a $near or $nearSphere operator (call near() or nearSphere() first)'
            );
        }

        if (isset($query['$near']['$geometry'])) {
            $query['$near']['$maxDistance'] = $maxDistance;
        } elseif (isset($query['$nearSphere']['$geometry'])) {
            $query['$nearSphere']['$maxDistance'] = $maxDistance;
        } else {
            $query['$maxDistance'] = $maxDistance;
        }

        return $this;
    }

    /**
     * Updates the value of the field to a specified value if the specified value is less than the current value of the field.
     *
     * @see Builder::min()
     * @see http://docs.mongodb.org/manual/reference/operator/update/min/
     * @param mixed $value
     * @return $this
     */
    public function min($value)
    {
        $this->requiresCurrentField();
        $this->newObj['$min'][$this->currentField] = $value;
        return $this;
    }

    /**
     * Set the $minDistance option for $near or $nearSphere criteria.
     *
     * This method must be called after near() or nearSphere(), since placement
     * of the $minDistance option depends on whether a GeoJSON point or legacy
     * coordinates were provided for $near/$nearSphere.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/minDistance/
     * @param float $minDistance
     * @return $this
     * @throws \BadMethodCallException if the query does not already have $near or $nearSphere criteria
     */
    public function minDistance($minDistance)
    {
        if ($this->currentField) {
            $query = &$this->query[$this->currentField];
        } else {
            $query = &$this->query;
        }

        if ( ! isset($query['$near']) && ! isset($query['$nearSphere'])) {
            throw new \BadMethodCallException(
                'This method requires a $near or $nearSphere operator (call near() or nearSphere() first)'
            );
        }

        if (isset($query['$near']['$geometry'])) {
            $query['$near']['$minDistance'] = $minDistance;
        } elseif (isset($query['$nearSphere']['$geometry'])) {
            $query['$nearSphere']['$minDistance'] = $minDistance;
        } else {
            $query['$minDistance'] = $minDistance;
        }

        return $this;
    }

    /**
     * Specify $mod criteria for the current field.
     *
     * @see Builder::mod()
     * @see http://docs.mongodb.org/manual/reference/operator/mod/
     * @param float|integer $divisor
     * @param float|integer $remainder
     * @return $this
     */
    public function mod($divisor, $remainder = 0)
    {
        return $this->operator('$mod', [$divisor, $remainder]);
    }

    /**
     * Multiply the current field.
     *
     * If the field does not exist, it will be set to 0.
     *
     * @see Builder::mul()
     * @see http://docs.mongodb.org/manual/reference/operator/mul/
     * @param float|integer $value
     * @return $this
     */
    public function mul($value)
    {
        $this->requiresCurrentField();
        $this->newObj['$mul'][$this->currentField] = $value;
        return $this;
    }

    /**
     * Add $near criteria to the expression.
     *
     * A GeoJSON point may be provided as the first and only argument for
     * 2dsphere queries. This single parameter may be a GeoJSON point object or
     * an array corresponding to the point's JSON representation.
     *
     * @see Builder::near()
     * @see http://docs.mongodb.org/manual/reference/operator/near/
     * @param float|array|Point $x
     * @param float $y
     * @return $this
     */
    public function near($x, $y = null)
    {
        if ($x instanceof Point) {
            $x = $x->jsonSerialize();
        }

        if (is_array($x)) {
            return $this->operator('$near', ['$geometry' => $x]);
        }

        return $this->operator('$near', [$x, $y]);
    }

    /**
     * Add $nearSphere criteria to the expression.
     *
     * A GeoJSON point may be provided as the first and only argument for
     * 2dsphere queries. This single parameter may be a GeoJSON point object or
     * an array corresponding to the point's JSON representation.
     *
     * @see Builder::nearSphere()
     * @see http://docs.mongodb.org/manual/reference/operator/nearSphere/
     * @param float|array|Point $x
     * @param float $y
     * @return $this
     */
    public function nearSphere($x, $y = null)
    {
        if ($x instanceof Point) {
            $x = $x->jsonSerialize();
        }

        if (is_array($x)) {
            return $this->operator('$nearSphere', ['$geometry' => $x]);
        }

        return $this->operator('$nearSphere', [$x, $y]);
    }

    /**
     * Negates an expression for the current field.
     *
     * @see Builder::not()
     * @see http://docs.mongodb.org/manual/reference/operator/not/
     * @param array|Expr $expression
     * @return $this
     */
    public function not($expression)
    {
        return $this->operator('$not', $expression instanceof Expr ? $expression->getQuery() : $expression);
    }

    /**
     * Specify $ne criteria for the current field.
     *
     * @see Builder::notEqual()
     * @see http://docs.mongodb.org/manual/reference/operator/ne/
     * @param mixed $value
     * @return $this
     */
    public function notEqual($value)
    {
        return $this->operator('$ne', $value);
    }

    /**
     * Specify $nin criteria for the current field.
     *
     * @see Builder::notIn()
     * @see http://docs.mongodb.org/manual/reference/operator/nin/
     * @param array $values
     * @return $this
     */
    public function notIn(array $values)
    {
        return $this->operator('$nin', array_values($values));
    }

    /**
     * Defines an operator and value on the expression.
     *
     * If there is a current field, the operator will be set on it; otherwise,
     * the operator is set at the top level of the query.
     *
     * @param string $operator
     * @param mixed $value
     * @return $this
     */
    public function operator($operator, $value)
    {
        $this->wrapEqualityCriteria();

        if ($this->currentField) {
            $this->query[$this->currentField][$operator] = $value;
        } else {
            $this->query[$operator] = $value;
        }
        return $this;
    }

    /**
     * Remove the first element from the current array field.
     *
     * @see Builder::popFirst()
     * @see http://docs.mongodb.org/manual/reference/operator/pop/
     * @return $this
     */
    public function popFirst()
    {
        $this->requiresCurrentField();
        $this->newObj['$pop'][$this->currentField] = 1;
        return $this;
    }

    /**
     * Remove the last element from the current array field.
     *
     * @see Builder::popLast()
     * @see http://docs.mongodb.org/manual/reference/operator/pop/
     * @return $this
     */
    public function popLast()
    {
        $this->requiresCurrentField();
        $this->newObj['$pop'][$this->currentField] = -1;
        return $this;
    }

    /**
     * Add $position criteria to the expression for a $push operation.
     *
     * This is useful in conjunction with {@link Expr::each()} for a
     * {@link Expr::push()} operation.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/update/position/
     * @param integer $position
     * @return $this
     */
    public function position($position)
    {
        return $this->operator('$position', $position);
    }

    /**
     * Remove all elements matching the given value or expression from the
     * current array field.
     *
     * @see Builder::pull()
     * @see http://docs.mongodb.org/manual/reference/operator/pull/
     * @param mixed|Expr $valueOrExpression
     * @return $this
     */
    public function pull($valueOrExpression)
    {
        if ($valueOrExpression instanceof Expr) {
            $valueOrExpression = $valueOrExpression->getQuery();
        }

        $this->requiresCurrentField();
        $this->newObj['$pull'][$this->currentField] = $valueOrExpression;
        return $this;
    }

    /**
     * Remove all elements matching any of the given values from the current
     * array field.
     *
     * @see Builder::pullAll()
     * @see http://docs.mongodb.org/manual/reference/operator/pullAll/
     * @param array $values
     * @return $this
     */
    public function pullAll(array $values)
    {
        $this->requiresCurrentField();
        $this->newObj['$pullAll'][$this->currentField] = $values;
        return $this;
    }

    /**
     * Append one or more values to the current array field.
     *
     * If the field does not exist, it will be set to an array containing the
     * value(s) in the argument. If the field is not an array, the query
     * will yield an error.
     *
     * Multiple values may be specified by providing an Expr object and using
     * {@link Expr::each()}. {@link Expr::slice()} and {@link Expr::sort()} may
     * also be used to limit and order array elements, respectively.
     *
     * @see Builder::push()
     * @see http://docs.mongodb.org/manual/reference/operator/push/
     * @see http://docs.mongodb.org/manual/reference/operator/each/
     * @see http://docs.mongodb.org/manual/reference/operator/slice/
     * @see http://docs.mongodb.org/manual/reference/operator/sort/
     * @param mixed|Expr $valueOrExpression
     * @return $this
     */
    public function push($valueOrExpression)
    {
        if ($valueOrExpression instanceof Expr) {
            $valueOrExpression = array_merge(
                ['$each' => []],
                $valueOrExpression->getQuery()
            );
        }

        $this->requiresCurrentField();
        $this->newObj['$push'][$this->currentField] = $valueOrExpression;
        return $this;
    }

    /**
     * Append multiple values to the current array field.
     *
     * If the field does not exist, it will be set to an array containing the
     * values in the argument. If the field is not an array, the query will
     * yield an error.
     *
     * This operator is deprecated in MongoDB 2.4. {@link Expr::push()} and
     * {@link Expr::each()} should be used in its place.
     *
     * @see Builder::pushAll()
     * @see http://docs.mongodb.org/manual/reference/operator/pushAll/
     * @param array $values
     * @return $this
     */
    public function pushAll(array $values)
    {
        $this->requiresCurrentField();
        $this->newObj['$pushAll'][$this->currentField] = $values;
        return $this;
    }

    /**
     * Specify $gte and $lt criteria for the current field.
     *
     * This method is shorthand for specifying $gte criteria on the lower bound
     * and $lt criteria on the upper bound. The upper bound is not inclusive.
     *
     * @see Builder::range()
     * @param mixed $start
     * @param mixed $end
     * @return $this
     */
    public function range($start, $end)
    {
        return $this->operator('$gte', $start)->operator('$lt', $end);
    }

    /**
     * Checks that the value of the current field is a reference to the supplied document.
     *
     * @param object $document
     * @return Expr
     */
    public function references($document)
    {
        if ($this->currentField) {
            $mapping = $this->getReferenceMapping();
            $reference = $this->dm->createReference($document, $mapping);
            $storeAs = array_key_exists('storeAs', $mapping) ? $mapping['storeAs'] : null;

            switch ($storeAs) {
                case ClassMetadataInfo::REFERENCE_STORE_AS_ID:
                    $this->query[$mapping['name']] = $reference;
                    return $this;
                    break;

                case ClassMetadataInfo::REFERENCE_STORE_AS_REF:
                    $keys = ['id' => true];
                    break;

                case ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF:
                case ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF_WITH_DB:
                    $keys = ['$ref' => true, '$id' => true, '$db' => true];

                    if ($storeAs === ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF) {
                        unset($keys['$db']);
                    }

                    if (isset($mapping['targetDocument'])) {
                        unset($keys['$ref'], $keys['$db']);
                    }
                    break;

                default:
                    throw new \InvalidArgumentException("Reference type {$storeAs} is invalid.");
            }

            foreach ($keys as $key => $value) {
                $this->query[$mapping['name'] . '.' . $key] = $reference[$key];
            }
        } else {
            @trigger_error('Calling ' . __METHOD__ . ' without a current field set will no longer be possible in ODM 2.0.', E_USER_DEPRECATED);

            $this->query = $this->dm->createDBRef($document);
        }

        return $this;
    }

    /**
     * Rename the current field.
     *
     * @see Builder::rename()
     * @see http://docs.mongodb.org/manual/reference/operator/rename/
     * @param string $name
     * @return $this
     */
    public function rename($name)
    {
        $this->requiresCurrentField();
        $this->newObj['$rename'][$this->currentField] = $name;
        return $this;
    }

    /**
     * Set the current field to a value.
     *
     * This is only relevant for insert, update, or findAndUpdate queries. For
     * update and findAndUpdate queries, the $atomic parameter will determine
     * whether or not a $set operator is used.
     *
     * @see Builder::set()
     * @see http://docs.mongodb.org/manual/reference/operator/set/
     * @param mixed $value
     * @param boolean $atomic
     * @return $this
     */
    public function set($value, $atomic = true)
    {
        $this->requiresCurrentField();

        if ($atomic) {
            $this->newObj['$set'][$this->currentField] = $value;
            return $this;
        }

        if (strpos($this->currentField, '.') === false) {
            $this->newObj[$this->currentField] = $value;
            return $this;
        }

        $keys = explode('.', $this->currentField);
        $current = &$this->newObj;
        foreach ($keys as $key) {
            $current = &$current[$key];
        }
        $current = $value;

        return $this;
    }

    /**
     * Sets ClassMetadata for document being queried.
     *
     * @param ClassMetadata $class
     */
    public function setClassMetadata(ClassMetadata $class)
    {
        $this->class = $class;
    }

    /**
     * Set the "new object".
     *
     * @see Builder::setNewObj()
     * @param array $newObj
     * @return $this
     */
    public function setNewObj(array $newObj)
    {
        $this->newObj = $newObj;
        return $this;
    }

    /**
     * Set the current field to the value if the document is inserted in an
     * upsert operation.
     *
     * If an update operation with upsert: true results in an insert of a
     * document, then $setOnInsert assigns the specified values to the fields in
     * the document. If the update operation does not result in an insert,
     * $setOnInsert does nothing.
     *
     * @see Builder::setOnInsert()
     * @see https://docs.mongodb.org/manual/reference/operator/update/setOnInsert/
     * @param mixed $value
     * @return $this
     */
    public function setOnInsert($value)
    {
        $this->requiresCurrentField();
        $this->newObj['$setOnInsert'][$this->currentField] = $value;

        return $this;
    }

    /**
     * Set the query criteria.
     *
     * @see Builder::setQueryArray()
     * @param array $query
     * @return $this
     */
    public function setQuery(array $query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Specify $size criteria for the current field.
     *
     * @see Builder::size()
     * @see http://docs.mongodb.org/manual/reference/operator/size/
     * @param integer $size
     * @return $this
     */
    public function size($size)
    {
        return $this->operator('$size', (integer) $size);
    }

    /**
     * Add $slice criteria to the expression for a $push operation.
     *
     * This is useful in conjunction with {@link Expr::each()} for a
     * {@link Expr::push()} operation. {@link Builder::selectSlice()} should be
     * used for specifying $slice for a query projection.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/slice/
     * @param integer $slice
     * @return $this
     */
    public function slice($slice)
    {
        return $this->operator('$slice', $slice);
    }

    /**
     * Add $sort criteria to the expression for a $push operation.
     *
     * If sorting by multiple fields, the first argument should be an array of
     * field name (key) and order (value) pairs.
     *
     * This is useful in conjunction with {@link Expr::each()} for a
     * {@link Expr::push()} operation. {@link Builder::sort()} should be used to
     * sort the results of a query.
     *
     * @see http://docs.mongodb.org/manual/reference/operator/sort/
     * @param array|string $fieldName Field name or array of field/order pairs
     * @param int|string $order       Field order (if one field is specified)
     * @return $this
     */
    public function sort($fieldName, $order = null)
    {
        $fields = is_array($fieldName) ? $fieldName : [$fieldName => $order];

        return $this->operator('$sort', array_map([$this, 'normalizeSortOrder'], $fields));
    }

    /**
     * Specify $text criteria for the current query.
     *
     * The $language option may be set with {@link Expr::language()}.
     *
     * @see Builder::text()
     * @see http://docs.mongodb.org/master/reference/operator/query/text/
     * @param string $search
     * @return $this
     */
    public function text($search)
    {
        $this->query['$text'] = ['$search' => (string) $search];
        return $this;
    }

    /**
     * Specify $type criteria for the current field.
     *
     * @todo Remove support for string $type argument in 2.0
     * @see Builder::type()
     * @see http://docs.mongodb.org/manual/reference/operator/type/
     * @param integer $type
     * @return $this
     */
    public function type($type)
    {
        if (is_string($type)) {
            $map = [
                'double' => 1,
                'string' => 2,
                'object' => 3,
                'array' => 4,
                'binary' => 5,
                'undefined' => 6,
                'objectid' => 7,
                'boolean' => 8,
                'date' => 9,
                'null' => 10,
                'regex' => 11,
                'jscode' => 13,
                'symbol' => 14,
                'jscodewithscope' => 15,
                'integer32' => 16,
                'timestamp' => 17,
                'integer64' => 18,
                'maxkey' => 127,
                'minkey' => 255,
            ];

            $type = isset($map[$type]) ? $map[$type] : $type;
        }

        return $this->operator('$type', $type);
    }

    /**
     * Unset the current field.
     *
     * The field will be removed from the document (not set to null).
     *
     * @see Builder::unsetField()
     * @see http://docs.mongodb.org/manual/reference/operator/unset/
     * @return $this
     */
    public function unsetField()
    {
        $this->requiresCurrentField();
        $this->newObj['$unset'][$this->currentField] = 1;
        return $this;
    }

    /**
     * Specify a JavaScript expression to use for matching documents.
     *
     * @see Builder::where()
     * @see http://docs.mongodb.org/manual/reference/operator/where/
     * @param string|\MongoCode $javascript
     * @return $this
     */
    public function where($javascript)
    {
        $this->query['$where'] = $javascript;
        return $this;
    }

    /**
     * Add $within criteria with a $box shape to the expression.
     *
     * @deprecated 1.1 MongoDB 2.4 deprecated $within in favor of $geoWithin
     * @see Expr::geoWithinBox()
     * @see http://docs.mongodb.org/manual/reference/operator/box/
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @return $this
     */
    public function withinBox($x1, $y1, $x2, $y2)
    {
        $shape = ['$box' => [[$x1, $y1], [$x2, $y2]]];

        return $this->operator('$within', $shape);
    }

    /**
     * Add $within criteria with a $center shape to the expression.
     *
     * @deprecated 1.1 MongoDB 2.4 deprecated $within in favor of $geoWithin
     * @see Expr::geoWithinCenter()
     * @see http://docs.mongodb.org/manual/reference/operator/center/
     * @param float $x
     * @param float $y
     * @param float $radius
     * @return $this
     */
    public function withinCenter($x, $y, $radius)
    {
        $shape = ['$center' => [[$x, $y], $radius]];

        return $this->operator('$within', $shape);
    }

    /**
     * Add $within criteria with a $centerSphere shape to the expression.
     *
     * @deprecated 1.1 MongoDB 2.4 deprecated $within in favor of $geoWithin
     * @see Expr::geoWithinCenterSphere()
     * @see http://docs.mongodb.org/manual/reference/operator/centerSphere/
     * @param float $x
     * @param float $y
     * @param float $radius
     * @return $this
     */
    public function withinCenterSphere($x, $y, $radius)
    {
        $shape = ['$centerSphere' => [[$x, $y], $radius]];

        return $this->operator('$within', $shape);
    }

    /**
     * Add $within criteria with a $polygon shape to the expression.
     *
     * Point coordinates are in x, y order (easting, northing for projected
     * coordinates, longitude, latitude for geographic coordinates).
     *
     * The last point coordinate is implicitly connected with the first.
     *
     * @deprecated 1.1 MongoDB 2.4 deprecated $within in favor of $geoWithin
     * @see Expr::geoWithinPolygon()
     * @see http://docs.mongodb.org/manual/reference/operator/polygon/
     * @param array $point,... Three or more point coordinate tuples
     * @return $this
     * @throws \InvalidArgumentException if less than three points are given
     */
    public function withinPolygon(/* array($x1, $y1), array($x2, $y2), ... */)
    {
        if (func_num_args() < 3) {
            throw new \InvalidArgumentException('Polygon must be defined by three or more points.');
        }

        $shape = ['$polygon' => func_get_args()];

        return $this->operator('$within', $shape);
    }

    /**
     * Gets reference mapping for current field from current class or its descendants.
     *
     * @return array
     * @throws MappingException
     */
    private function getReferenceMapping()
    {
        $mapping = null;
        try {
            $mapping = $this->class->getFieldMapping($this->currentField);
        } catch (MappingException $e) {
            if (empty($this->class->discriminatorMap)) {
                throw $e;
            }
            $foundIn = null;
            foreach ($this->class->discriminatorMap as $child) {
                $childClass = $this->dm->getClassMetadata($child);
                if ($childClass->hasAssociation($this->currentField)) {
                    if ($mapping !== null && $mapping !== $childClass->getFieldMapping($this->currentField)) {
                        throw MappingException::referenceFieldConflict($this->currentField, $foundIn->name, $childClass->name);
                    }
                    $mapping = $childClass->getFieldMapping($this->currentField);
                    $foundIn = $childClass;
                }
            }
            if ($mapping === null) {
                throw MappingException::mappingNotFoundInClassNorDescendants($this->class->name, $this->currentField);
            }
        }
        return $mapping;
    }

    /**
     * @param int|string $order
     *
     * @return int
     */
    private function normalizeSortOrder($order): int
    {
        if (is_string($order)) {
            $order = strtolower($order) === 'asc' ? 1 : -1;
        }

        return (int) $order;
    }

    /**
     * Ensure that a current field has been set.
     *
     * @throws \LogicException if a current field has not been set
     */
    private function requiresCurrentField()
    {
        if ( ! $this->currentField) {
            throw new \LogicException('This method requires you set a current field using field().');
        }
    }

    /**
     * Wraps equality criteria with an operator.
     *
     * If equality criteria was previously specified for a field, it cannot be
     * merged with other operators without first being wrapped in an operator of
     * its own. Ideally, we would wrap it with $eq, but that is only available
     * in MongoDB 2.8. Using a single-element $in is backwards compatible.
     *
     * @see Expr::operator()
     */
    private function wrapEqualityCriteria()
    {
        /* If the current field has no criteria yet, do nothing. This ensures
         * that we do not inadvertently inject {"$in": null} into the query.
         */
        if ($this->currentField && ! isset($this->query[$this->currentField]) && ! array_key_exists($this->currentField, $this->query)) {
            return;
        }

        if ($this->currentField) {
            $query = &$this->query[$this->currentField];
        } else {
            $query = &$this->query;
        }

        /* If the query is an empty array, we'll assume that the user has not
         * specified criteria. Otherwise, check if the array includes a query
         * operator (checking the first key is sufficient). If neither of these
         * conditions are met, we'll wrap the query value with $in.
         */
        if (is_array($query) && (empty($query) || strpos(key($query), '$') === 0)) {
            return;
        }

        $query = ['$in' => [$query]];
    }
}
