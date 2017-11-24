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
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use GeoJson\Geometry\Geometry;
use GeoJson\Geometry\Point;
use MongoDB\Collection;
use MongoDB\Driver\ReadPreference;

/**
 * Query builder for ODM.
 *
 * @since       1.0
 */
class Builder
{
    /**
     * The DocumentManager instance for this query
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The ClassMetadata instance.
     *
     * @var \Doctrine\ODM\MongoDB\Mapping\ClassMetadata
     */
    private $class;

    /**
     * The current field we are operating on.
     *
     * @todo Change this to private once ODM requires doctrine/mongodb 1.1+
     * @var string
     */
    protected $currentField;

    /**
     * Whether or not to hydrate the data to documents.
     *
     * @var boolean
     */
    private $hydrate = true;

    /**
     * Whether or not to refresh the data for documents that are already in the identity map.
     *
     * @var boolean
     */
    private $refresh = false;

    /**
     * Array of primer Closure instances.
     *
     * @var array
     */
    private $primers = array();

    /**
     * Whether or not to register documents in UnitOfWork.
     *
     * @var bool
     */
    private $readOnly;

    /**
     * The Collection instance.
     *
     * @var Collection
     */
    private $collection;

    /**
     * Array containing the query data.
     *
     * @var array
     */
    private $query = ['type' => Query::TYPE_FIND];

    /**
     * The Expr instance used for building this query.
     *
     * This object includes the query criteria and the "new object" used for
     * insert and update queries.
     *
     * @var Expr $expr
     */
    private $expr;

    /**
     * Construct a Builder
     *
     * @param DocumentManager $dm
     * @param string[]|string|null $documentName (optional) an array of document names, the document name, or none
     */
    public function __construct(DocumentManager $dm, $documentName = null)
    {
        $this->dm = $dm;
        $this->expr = new Expr($dm);
        if ($documentName !== null) {
            $this->setDocumentName($documentName);
        }
    }

    public function __clone()
    {
        $this->expr = clone $this->expr;
    }

    /**
     * Add one or more $and clauses to the current query.
     *
     * You can create a new expression using the {@link Builder::expr()} method.
     *
     * @see Expr::addAnd()
     * @see http://docs.mongodb.org/manual/reference/operator/and/
     * @param array|Expr $expression
     * @return $this
     */
    public function addAnd($expression /* , $expression2, ... */)
    {
        $this->expr->addAnd(...func_get_args());
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
     * @deprecated 1.1 Use {@link Builder::addToSet()} with {@link Expr::each()}; Will be removed in 2.0
     * @see Expr::addManyToSet()
     * @see http://docs.mongodb.org/manual/reference/operator/addToSet/
     * @see http://docs.mongodb.org/manual/reference/operator/each/
     * @param array $values
     * @return $this
     */
    public function addManyToSet(array $values)
    {
        $this->expr->addManyToSet($values);
        return $this;
    }

    /**
     * Add one or more $nor clauses to the current query.
     *
     * You can create a new expression using the {@link Builder::expr()} method.
     *
     * @see Expr::addNor()
     * @see http://docs.mongodb.org/manual/reference/operator/nor/
     * @param array|Expr $expression
     * @return $this
     */
    public function addNor($expression /* , $expression2, ... */)
    {
        $this->expr->addNor(...func_get_args());
        return $this;
    }

    /**
     * Add one or more $or clauses to the current query.
     *
     * You can create a new expression using the {@link Builder::expr()} method.
     *
     * @see Expr::addOr()
     * @see http://docs.mongodb.org/manual/reference/operator/or/
     * @param array|Expr $expression
     * @return $this
     */
    public function addOr($expression /* , $expression2, ... */)
    {
        $this->expr->addOr(...func_get_args());
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
     * @see Expr::addToSet()
     * @see http://docs.mongodb.org/manual/reference/operator/addToSet/
     * @see http://docs.mongodb.org/manual/reference/operator/each/
     * @param mixed|Expr $valueOrExpression
     * @return $this
     */
    public function addToSet($valueOrExpression)
    {
        $this->expr->addToSet($valueOrExpression);
        return $this;
    }

    /**
     * Specify $all criteria for the current field.
     *
     * @see Expr::all()
     * @see http://docs.mongodb.org/manual/reference/operator/all/
     * @param array $values
     * @return $this
     */
    public function all(array $values)
    {
        $this->expr->all($values);
        return $this;
    }

    /**
     * Apply a bitwise and operation on the current field.
     *
     * @see Expr::bitAnd()
     * @see http://docs.mongodb.org/manual/reference/operator/update/bit/
     * @param int $value
     * @return $this
     */
    public function bitAnd($value)
    {
        $this->expr->bitAnd($value);
        return $this;
    }

    /**
     * Apply a bitwise or operation on the current field.
     *
     * @see Expr::bitOr()
     * @see http://docs.mongodb.org/manual/reference/operator/update/bit/
     * @param int $value
     * @return $this
     */
    public function bitOr($value)
    {
        $this->expr->bitOr($value);
        return $this;
    }

    /**
     * Matches documents where all of the bit positions given by the query are
     * clear.
     *
     * @see Expr::bitsAllClear()
     * @see https://docs.mongodb.org/manual/reference/operator/query/bitsAllClear/
     * @param int|array|\MongoBinData $value
     * @return $this
     */
    public function bitsAllClear($value)
    {
        $this->expr->bitsAllClear($value);
        return $this;
    }

    /**
     * Matches documents where all of the bit positions given by the query are
     * set.
     *
     * @see Expr::bitsAllSet()
     * @see https://docs.mongodb.org/manual/reference/operator/query/bitsAllSet/
     * @param int|array|\MongoBinData $value
     * @return $this
     */
    public function bitsAllSet($value)
    {
        $this->expr->bitsAllSet($value);
        return $this;
    }

    /**
     * Matches documents where any of the bit positions given by the query are
     * clear.
     *
     * @see Expr::bitsAnyClear()
     * @see https://docs.mongodb.org/manual/reference/operator/query/bitsAnyClear/
     * @param int|array|\MongoBinData $value
     * @return $this
     */
    public function bitsAnyClear($value)
    {
        $this->expr->bitsAnyClear($value);
        return $this;
    }

    /**
     * Matches documents where any of the bit positions given by the query are
     * set.
     *
     * @see Expr::bitsAnySet()
     * @see https://docs.mongodb.org/manual/reference/operator/query/bitsAnySet/
     * @param int|array|\MongoBinData $value
     * @return $this
     */
    public function bitsAnySet($value)
    {
        $this->expr->bitsAnySet($value);
        return $this;
    }

    /**
     * Apply a bitwise xor operation on the current field.
     *
     * @see Expr::bitXor()
     * @see http://docs.mongodb.org/manual/reference/operator/update/bit/
     * @param int $value
     * @return $this
     */
    public function bitXor($value)
    {
        $this->expr->bitXor($value);
        return $this;
    }

    /**
     * A boolean flag to enable or disable case sensitive search for $text
     * criteria.
     *
     * This method must be called after text().
     *
     * @see Expr::caseSensitive()
     * @see http://docs.mongodb.org/manual/reference/operator/text/
     * @param bool $caseSensitive
     * @return $this
     * @throws \BadMethodCallException if the query does not already have $text criteria
     *
     * @since 1.3
     */
    public function caseSensitive($caseSensitive)
    {
        $this->expr->caseSensitive($caseSensitive);
        return $this;
    }

    /**
     * Associates a comment to any expression taking a query predicate.
     *
     * @see Expr::comment()
     * @see http://docs.mongodb.org/manual/reference/operator/query/comment/
     * @param string $comment
     * @return $this
     */
    public function comment($comment)
    {
        $this->expr->comment($comment);
        return $this;
    }

    /**
     * Change the query type to count.
     *
     * @return $this
     */
    public function count()
    {
        $this->query['type'] = Query::TYPE_COUNT;
        return $this;
    }

    /**
     * Sets the value of the current field to the current date, either as a date or a timestamp.
     *
     * @see Expr::currentDate()
     * @see http://docs.mongodb.org/manual/reference/operator/currentDate/
     * @param string $type
     * @return $this
     */
    public function currentDate($type = 'date')
    {
        $this->expr->currentDate($type);
        return $this;
    }

    /**
     * Return an array of information about the Builder state for debugging.
     *
     * The $name parameter may be used to return a specific key from the
     * internal $query array property. If omitted, the entire array will be
     * returned.
     *
     * @param string $name
     * @return mixed
     */
    public function debug($name = null)
    {
        return $name !== null ? $this->query[$name] : $this->query;
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
        $this->expr->diacriticSensitive($diacriticSensitive);
        return $this;
    }

    /**
     * Set the "distanceMultiplier" option for a geoNear command query.
     *
     * @param float $distanceMultiplier
     * @return $this
     * @throws \BadMethodCallException if the query is not a geoNear command
     */
    public function distanceMultiplier($distanceMultiplier)
    {
        if ($this->query['type'] !== Query::TYPE_GEO_NEAR) {
            throw new \BadMethodCallException('This method requires a geoNear command (call geoNear() first)');
        }

        $this->query['geoNear']['options']['distanceMultiplier'] = $distanceMultiplier;
        return $this;
    }

    /**
     * Change the query type to a distinct command.
     *
     * @see http://docs.mongodb.org/manual/reference/command/distinct/
     * @param string $field
     * @return $this
     */
    public function distinct($field)
    {
        $this->query['type'] = Query::TYPE_DISTINCT;
        $this->query['distinct'] = $field;
        return $this;
    }

    /**
     * Set whether the query should return its result as an EagerCursor.
     *
     * @param boolean $bool
     * @return $this
     */
    public function eagerCursor($bool = true)
    {
        if ( ! $bool && ! empty($this->primers)) {
            throw new \BadMethodCallException("Can't set eagerCursor to false when using reference primers");
        }

        $this->query['eagerCursor'] = (boolean) $bool;
        return $this;
    }

    /**
     * Specify $elemMatch criteria for the current field.
     *
     * You can create a new expression using the {@link Builder::expr()} method.
     *
     * @see Expr::elemMatch()
     * @see http://docs.mongodb.org/manual/reference/operator/elemMatch/
     * @param array|Expr $expression
     * @return $this
     */
    public function elemMatch($expression)
    {
        $this->expr->elemMatch($expression);
        return $this;
    }

    /**
     * Specify an equality match for the current field.
     *
     * @see Expr::equals()
     * @param mixed $value
     * @return $this
     */
    public function equals($value)
    {
        $this->expr->equals($value);
        return $this;
    }

    /**
     * Set one or more fields to be excluded from the query projection.
     *
     * If fields have been selected for inclusion, only the "_id" field may be
     * excluded.
     *
     * @param array|string $fieldName,...
     * @return $this
     */
    public function exclude($fieldName = null)
    {
        if ( ! isset($this->query['select'])) {
            $this->query['select'] = [];
        }

        $fieldNames = is_array($fieldName) ? $fieldName : func_get_args();

        foreach ($fieldNames as $fieldName) {
            $this->query['select'][$fieldName] = 0;
        }

        return $this;
    }

    /**
     * Specify $exists criteria for the current field.
     *
     * @see Expr::exists()
     * @see http://docs.mongodb.org/manual/reference/operator/exists/
     * @param boolean $bool
     * @return $this
     */
    public function exists($bool)
    {
        $this->expr->exists((boolean) $bool);
        return $this;
    }

    /**
     * Create a new Expr instance that can be used as an expression with the Builder
     *
     * @return Expr $expr
     */
    public function expr()
    {
        $expr = new Expr($this->dm);
        $expr->setClassMetadata($this->class);

        return $expr;
    }

    /**
     * Set the current field to operate on.
     *
     * @param string $field
     * @return $this
     */
    public function field($field)
    {
        $this->currentField = $field;
        $this->expr->field((string) $field);

        return $this;
    }

    /**
     * Set the "finalize" option for a mapReduce or group command.
     *
     * @param string|\MongoCode $finalize
     * @return $this
     * @throws \BadMethodCallException if the query is not a mapReduce or group command
     */
    public function finalize($finalize)
    {
        switch ($this->query['type']) {
            case Query::TYPE_MAP_REDUCE:
                $this->query['mapReduce']['options']['finalize'] = $finalize;
                break;

            case Query::TYPE_GROUP:
                $this->query['group']['options']['finalize'] = $finalize;
                break;

            default:
                throw new \BadMethodCallException('mapReduce(), map() or group() must be called before finalize()');
        }

        return $this;
    }

    /**
     * Change the query type to find and optionally set and change the class being queried.
     *
     * @param string $documentName
     * @return $this
     */
    public function find($documentName = null)
    {
        $this->setDocumentName($documentName);
        $this->query['type'] = Query::TYPE_FIND;

        return $this;
    }

    /**
     * @param string $documentName
     * @return $this
     */
    public function findAndRemove($documentName = null)
    {
        $this->setDocumentName($documentName);
        $this->query['type'] = Query::TYPE_FIND_AND_REMOVE;

        return $this;
    }

    /**
     * @param string $documentName
     * @return $this
     */
    public function findAndUpdate($documentName = null)
    {
        $this->setDocumentName($documentName);
        $this->query['type'] = Query::TYPE_FIND_AND_UPDATE;

        return $this;
    }

    /**
     * Add $geoIntersects criteria with a GeoJSON geometry to the query.
     *
     * The geometry parameter GeoJSON object or an array corresponding to the
     * geometry's JSON representation.
     *
     * @see Expr::geoIntersects()
     * @see http://docs.mongodb.org/manual/reference/operator/geoIntersects/
     * @param array|Geometry $geometry
     * @return $this
     */
    public function geoIntersects($geometry)
    {
        $this->expr->geoIntersects($geometry);
        return $this;
    }

    /**
     * Change the query type to a geoNear command.
     *
     * A GeoJSON point may be provided as the first and only argument for
     * 2dsphere queries. This single parameter may be a GeoJSON point object or
     * an array corresponding to the point's JSON representation. If GeoJSON is
     * used, the "spherical" option will default to true.
     *
     * This method sets the "near" option for the geoNear command. The "num"
     * option may be set using {@link Expr::limit()}. The "distanceMultiplier",
     * "maxDistance", "minDistance", and "spherical" options may be set using
     * their respective builder methods. Additional query criteria will be
     * assigned to the "query" option.
     *
     * @see http://docs.mongodb.org/manual/reference/command/geoNear/
     * @param float|array|Point $x
     * @param float $y
     * @return $this
     */
    public function geoNear($x, $y = null)
    {
        if ($x instanceof Point) {
            $x = $x->jsonSerialize();
        }

        $this->query['type'] = Query::TYPE_GEO_NEAR;
        $this->query['geoNear'] = [
            'near' => is_array($x) ? $x : [$x, $y],
            'options' => [
                'spherical' => is_array($x) && isset($x['type']),
            ],
        ];
        return $this;
    }

    /**
     * Add $geoWithin criteria with a GeoJSON geometry to the query.
     *
     * The geometry parameter GeoJSON object or an array corresponding to the
     * geometry's JSON representation.
     *
     * @see Expr::geoWithin()
     * @see http://docs.mongodb.org/manual/reference/operator/geoWithin/
     * @param array|Geometry $geometry
     * @return $this
     */
    public function geoWithin($geometry)
    {
        $this->expr->geoWithin($geometry);
        return $this;
    }

    /**
     * Add $geoWithin criteria with a $box shape to the query.
     *
     * A rectangular polygon will be constructed from a pair of coordinates
     * corresponding to the bottom left and top right corners.
     *
     * Note: the $box operator only supports legacy coordinate pairs and 2d
     * indexes. This cannot be used with 2dsphere indexes and GeoJSON shapes.
     *
     * @see Expr::geoWithinBox()
     * @see http://docs.mongodb.org/manual/reference/operator/box/
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @return $this
     */
    public function geoWithinBox($x1, $y1, $x2, $y2)
    {
        $this->expr->geoWithinBox($x1, $y1, $x2, $y2);
        return $this;
    }

    /**
     * Add $geoWithin criteria with a $center shape to the query.
     *
     * Note: the $center operator only supports legacy coordinate pairs and 2d
     * indexes. This cannot be used with 2dsphere indexes and GeoJSON shapes.
     *
     * @see Expr::geoWithinCenter()
     * @see http://docs.mongodb.org/manual/reference/operator/center/
     * @param float $x
     * @param float $y
     * @param float $radius
     * @return $this
     */
    public function geoWithinCenter($x, $y, $radius)
    {
        $this->expr->geoWithinCenter($x, $y, $radius);
        return $this;
    }

    /**
     * Add $geoWithin criteria with a $centerSphere shape to the query.
     *
     * Note: the $centerSphere operator supports both 2d and 2dsphere indexes.
     *
     * @see Expr::geoWithinCenterSphere()
     * @see http://docs.mongodb.org/manual/reference/operator/centerSphere/
     * @param float $x
     * @param float $y
     * @param float $radius
     * @return $this
     */
    public function geoWithinCenterSphere($x, $y, $radius)
    {
        $this->expr->geoWithinCenterSphere($x, $y, $radius);
        return $this;
    }

    /**
     * Add $geoWithin criteria with a $polygon shape to the query.
     *
     * Point coordinates are in x, y order (easting, northing for projected
     * coordinates, longitude, latitude for geographic coordinates).
     *
     * The last point coordinate is implicitly connected with the first.
     *
     * Note: the $polygon operator only supports legacy coordinate pairs and 2d
     * indexes. This cannot be used with 2dsphere indexes and GeoJSON shapes.
     *
     * @see Expr::geoWithinPolygon()
     * @see http://docs.mongodb.org/manual/reference/operator/polygon/
     * @param array $point,... Three or more point coordinate tuples
     * @return $this
     */
    public function geoWithinPolygon(/* array($x1, $y1), ... */)
    {
        $this->expr->geoWithinPolygon(...func_get_args());
        return $this;
    }

    /**
     * Return the expression's "new object".
     *
     * @see Expr::getNewObj()
     * @return array
     */
    public function getNewObj()
    {
        return $this->expr->getNewObj();
    }

    /**
     * Gets the Query executable.
     *
     * @param array $options
     * @return Query $query
     */
    public function getQuery(array $options = array())
    {
        if ($this->query['type'] === Query::TYPE_MAP_REDUCE) {
            $this->hydrate = false;
        }

        $documentPersister = $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);

        $query = $this->query;

        $query['query'] = $this->expr->getQuery();
        $query['query'] = $documentPersister->addDiscriminatorToPreparedQuery($query['query']);
        $query['query'] = $documentPersister->addFilterToPreparedQuery($query['query']);

        $query['newObj'] = $this->expr->getNewObj();

        if (isset($query['distinct'])) {
            $query['distinct'] = $documentPersister->prepareFieldName($query['distinct']);
        }

        if ($this->class->inheritanceType === ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_COLLECTION && ! empty($query['upsert']) &&
            (empty($query['query'][$this->class->discriminatorField]) || is_array($query['query'][$this->class->discriminatorField]))) {
            throw new \InvalidArgumentException('Upsert query that is to be performed on discriminated document does not have single ' .
                'discriminator. Either not use base class or set \'' . $this->class->discriminatorField . '\' field manually.');
        }

        if ( ! empty($query['select'])) {
            $query['select'] = $documentPersister->prepareProjection($query['select']);
            if ($this->hydrate && $this->class->inheritanceType === ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_COLLECTION
                && ! isset($query['select'][$this->class->discriminatorField])) {
                $includeMode = 0 < count(array_filter($query['select'], function($mode) { return $mode == 1; }));
                if ($includeMode && ! isset($query['select'][$this->class->discriminatorField])) {
                    $query['select'][$this->class->discriminatorField] = 1;
                }
            }
        }

        if (isset($query['sort'])) {
            $query['sort'] = $documentPersister->prepareSort($query['sort']);
        }

        if ($this->class->slaveOkay) {
            $query['slaveOkay'] = $this->class->slaveOkay;
        }

        if ($this->class->readPreference && ! array_key_exists('readPreference', $query)) {
            $query['readPreference'] = new ReadPreference($this->class->readPreference, $this->class->readPreferenceTags);
        }

        return new Query(
            $this->dm,
            $this->class,
            $this->collection,
            $query,
            $options,
            $this->hydrate,
            $this->refresh,
            $this->primers,
            $this->readOnly
        );
    }

    /**
     * Return the expression's query criteria.
     *
     * @see Expr::getQuery()
     * @return array
     */
    public function getQueryArray()
    {
        return $this->expr->getQuery();
    }

    /**
     * Get the type of this query.
     *
     * @return integer $type
     */
    public function getType()
    {
        return $this->query['type'];
    }

    /**
     * Specify $gt criteria for the current field.
     *
     * @see Expr::gt()
     * @see http://docs.mongodb.org/manual/reference/operator/gt/
     * @param mixed $value
     * @return $this
     */
    public function gt($value)
    {
        $this->expr->gt($value);
        return $this;
    }

    /**
     * Specify $gte criteria for the current field.
     *
     * @see Expr::gte()
     * @see http://docs.mongodb.org/manual/reference/operator/gte/
     * @param mixed $value
     * @return $this
     */
    public function gte($value)
    {
        $this->expr->gte($value);
        return $this;
    }

    /**
     * Set the index hint for the query.
     *
     * @param array|string $index
     * @return $this
     */
    public function hint($index)
    {
        $this->query['hint'] = $index;
        return $this;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function hydrate($bool = true)
    {
        $this->hydrate = $bool;
        return $this;
    }

    /**
     * Set the immortal cursor flag.
     *
     * @param boolean $bool
     * @return $this
     */
    public function immortal($bool = true)
    {
        $this->query['immortal'] = (boolean) $bool;
        return $this;
    }

    /**
     * Specify $in criteria for the current field.
     *
     * @see Expr::in()
     * @see http://docs.mongodb.org/manual/reference/operator/in/
     * @param array $values
     * @return $this
     */
    public function in(array $values)
    {
        $this->expr->in($values);
        return $this;
    }

    /**
     * Increment the current field.
     *
     * If the field does not exist, it will be set to this value.
     *
     * @see Expr::inc()
     * @see http://docs.mongodb.org/manual/reference/operator/inc/
     * @param float|integer $value
     * @return $this
     */
    public function inc($value)
    {
        $this->expr->inc($value);
        return $this;
    }

    /**
     * @param object $document
     * @return $this
     */
    public function includesReferenceTo($document)
    {
        $this->expr->includesReferenceTo($document);
        return $this;
    }

    /**
     * @param string $documentName
     * @return $this
     */
    public function insert($documentName = null)
    {
        $this->setDocumentName($documentName);
        $this->query['type'] = Query::TYPE_INSERT;

        return $this;
    }

    /**
     * Set the $language option for $text criteria.
     *
     * This method must be called after text().
     *
     * @see Expr::language()
     * @see http://docs.mongodb.org/manual/reference/operator/text/
     * @param string $language
     * @return $this
     */
    public function language($language)
    {
        $this->expr->language($language);
        return $this;
    }

    /**
     * Set the limit for the query.
     *
     * This is only relevant for find queries and geoNear and mapReduce
     * commands.
     *
     * @see Query::prepareCursor()
     * @param integer $limit
     * @return $this
     */
    public function limit($limit)
    {
        $this->query['limit'] = (integer) $limit;
        return $this;
    }

    /**
     * Specify $lt criteria for the current field.
     *
     * @see Expr::lte()
     * @see http://docs.mongodb.org/manual/reference/operator/lte/
     * @param mixed $value
     * @return $this
     */
    public function lt($value)
    {
        $this->expr->lt($value);
        return $this;
    }

    /**
     * Specify $lte criteria for the current field.
     *
     * @see Expr::lte()
     * @see http://docs.mongodb.org/manual/reference/operator/lte/
     * @param mixed $value
     * @return $this
     */
    public function lte($value)
    {
        $this->expr->lte($value);
        return $this;
    }

    /**
     * Change the query type to a mapReduce command.
     *
     * The "reduce" option is not specified when calling this method; it must
     * be set with the {@link Builder::reduce()} method.
     *
     * The "out" option defaults to inline, like {@link Builder::mapReduce()}.
     *
     * @see http://docs.mongodb.org/manual/reference/command/mapReduce/
     * @param string|\MongoCode $map
     * @return $this
     */
    public function map($map)
    {
        $this->query['type'] = Query::TYPE_MAP_REDUCE;
        $this->query['mapReduce'] = [
            'map' => $map,
            'reduce' => null,
            'out' => ['inline' => true],
            'options' => [],
        ];
        return $this;
    }

    /**
     * Change the query type to a mapReduce command.
     *
     * @see http://docs.mongodb.org/manual/reference/command/mapReduce/
     * @param string|\MongoCode $map
     * @param string|\MongoCode $reduce
     * @param array|string $out
     * @param array $options
     * @return $this
     */
    public function mapReduce($map, $reduce, $out = ['inline' => true], array $options = [])
    {
        $this->query['type'] = Query::TYPE_MAP_REDUCE;
        $this->query['mapReduce'] = [
            'map' => $map,
            'reduce' => $reduce,
            'out' => $out,
            'options' => $options
        ];
        return $this;
    }

    /**
     * Set additional options for a mapReduce command.
     *
     * @param array $options
     * @return $this
     * @throws \BadMethodCallException if the query is not a mapReduce command
     */
    public function mapReduceOptions(array $options)
    {
        if ($this->query['type'] !== Query::TYPE_MAP_REDUCE) {
            throw new \BadMethodCallException('This method requires a mapReduce command (call map() or mapReduce() first)');
        }

        $this->query['mapReduce']['options'] = $options;
        return $this;
    }

    /**
     * Updates the value of the field to a specified value if the specified value is greater than the current value of the field.
     *
     * @see Expr::max()
     * @see http://docs.mongodb.org/manual/reference/operator/update/max/
     * @param mixed $value
     * @return $this
     */
    public function max($value)
    {
        $this->expr->max($value);
        return $this;
    }

    /**
     * Set the "maxDistance" option for a geoNear command query or add
     * $maxDistance criteria to the query.
     *
     * If the query is a geoNear command ({@link Expr::geoNear()} was called),
     * the "maxDistance" command option will be set; otherwise, $maxDistance
     * will be added to the current expression.
     *
     * If the query uses GeoJSON points, $maxDistance will be interpreted in
     * meters. If legacy point coordinates are used, $maxDistance will be
     * interpreted in radians.
     *
     * @see Expr::maxDistance()
     * @see http://docs.mongodb.org/manual/reference/command/geoNear/
     * @see http://docs.mongodb.org/manual/reference/operator/maxDistance/
     * @see http://docs.mongodb.org/manual/reference/operator/near/
     * @see http://docs.mongodb.org/manual/reference/operator/nearSphere/
     * @param float $maxDistance
     * @return $this
     */
    public function maxDistance($maxDistance)
    {
        if ($this->query['type'] === Query::TYPE_GEO_NEAR) {
            $this->query['geoNear']['options']['maxDistance'] = $maxDistance;
        } else {
            $this->expr->maxDistance($maxDistance);
        }
        return $this;
    }

    /**
     * Specifies a cumulative time limit in milliseconds for processing operations on a cursor.
     *
     * @param int $ms
     * @return $this
     */
    public function maxTimeMS($ms)
    {
        $this->query['maxTimeMS'] = $ms;
        return $this;
    }

    /**
     * Updates the value of the field to a specified value if the specified value is less than the current value of the field.
     *
     * @see Expr::min()
     * @see http://docs.mongodb.org/manual/reference/operator/update/min/
     * @param mixed $value
     * @return $this
     */
    public function min($value)
    {
        $this->expr->min($value);
        return $this;
    }

    /**
     * Set the "minDistance" option for a geoNear command query or add
     * $minDistance criteria to the query.
     *
     * If the query is a geoNear command ({@link Expr::geoNear()} was called),
     * the "minDistance" command option will be set; otherwise, $minDistance
     * will be added to the current expression.
     *
     * If the query uses GeoJSON points, $minDistance will be interpreted in
     * meters. If legacy point coordinates are used, $minDistance will be
     * interpreted in radians.
     *
     * @see Expr::minDistance()
     * @see http://docs.mongodb.org/manual/reference/command/geoNear/
     * @see http://docs.mongodb.org/manual/reference/operator/minDistance/
     * @see http://docs.mongodb.org/manual/reference/operator/near/
     * @see http://docs.mongodb.org/manual/reference/operator/nearSphere/
     * @param float $minDistance
     * @return $this
     */
    public function minDistance($minDistance)
    {
        if ($this->query['type'] === Query::TYPE_GEO_NEAR) {
            $this->query['geoNear']['options']['minDistance'] = $minDistance;
        } else {
            $this->expr->minDistance($minDistance);
        }
        return $this;
    }

    /**
     * Specify $mod criteria for the current field.
     *
     * @see Expr::mod()
     * @see http://docs.mongodb.org/manual/reference/operator/mod/
     * @param float|integer $divisor
     * @param float|integer $remainder
     * @return $this
     */
    public function mod($divisor, $remainder = 0)
    {
        $this->expr->mod($divisor, $remainder);
        return $this;
    }

    /**
     * Multiply the current field.
     *
     * If the field does not exist, it will be set to 0.
     *
     * @see Expr::mul()
     * @see http://docs.mongodb.org/manual/reference/operator/mul/
     * @param float|integer $value
     * @return $this
     */
    public function mul($value)
    {
        $this->expr->mul($value);
        return $this;
    }

    /**
     * Set the "multiple" option for an update query.
     *
     * @param boolean $bool
     * @return $this
     *
     * @deprecated Deprecated in version 1.4 - use updateOne or updateMany instead
     */
    public function multiple($bool = true)
    {
        $this->query['multiple'] = (boolean) $bool;
        return $this;
    }

    /**
     * Add $near criteria to the query.
     *
     * A GeoJSON point may be provided as the first and only argument for
     * 2dsphere queries. This single parameter may be a GeoJSON point object or
     * an array corresponding to the point's JSON representation.
     *
     * @see Expr::near()
     * @see http://docs.mongodb.org/manual/reference/operator/near/
     * @param float|array|Point $x
     * @param float $y
     * @return $this
     */
    public function near($x, $y = null)
    {
        $this->expr->near($x, $y);
        return $this;
    }

    /**
     * Add $nearSphere criteria to the query.
     *
     * A GeoJSON point may be provided as the first and only argument for
     * 2dsphere queries. This single parameter may be a GeoJSON point object or
     * an array corresponding to the point's JSON representation.
     *
     * @see Expr::nearSphere()
     * @see http://docs.mongodb.org/manual/reference/operator/nearSphere/
     * @param float|array|Point $x
     * @param float $y
     * @return $this
     */
    public function nearSphere($x, $y = null)
    {
        $this->expr->nearSphere($x, $y);
        return $this;
    }

    /**
     * Negates an expression for the current field.
     *
     * You can create a new expression using the {@link Builder::expr()} method.
     *
     * @see Expr::not()
     * @see http://docs.mongodb.org/manual/reference/operator/not/
     * @param array|Expr $expression
     * @return $this
     */
    public function not($expression)
    {
        $this->expr->not($expression);
        return $this;
    }

    /**
     * Specify $ne criteria for the current field.
     *
     * @see Expr::notEqual()
     * @see http://docs.mongodb.org/manual/reference/operator/ne/
     * @param mixed $value
     * @return $this
     */
    public function notEqual($value)
    {
        $this->expr->notEqual($value);
        return $this;
    }

    /**
     * Specify $nin criteria for the current field.
     *
     * @see Expr::notIn()
     * @see http://docs.mongodb.org/manual/reference/operator/nin/
     * @param array $values
     * @return $this
     */
    public function notIn(array $values)
    {
        $this->expr->notIn($values);
        return $this;
    }

    /**
     * Set the "out" option for a mapReduce command.
     *
     * @param array|string $out
     * @return $this
     * @throws \BadMethodCallException if the query is not a mapReduce command
     */
    public function out($out)
    {
        if ($this->query['type'] !== Query::TYPE_MAP_REDUCE) {
            throw new \BadMethodCallException('This method requires a mapReduce command (call map() or mapReduce() first)');
        }

        $this->query['mapReduce']['out'] = $out;
        return $this;
    }

    /**
     * Remove the first element from the current array field.
     *
     * @see Expr::popFirst()
     * @see http://docs.mongodb.org/manual/reference/operator/pop/
     * @return $this
     */
    public function popFirst()
    {
        $this->expr->popFirst();
        return $this;
    }

    /**
     * Remove the last element from the current array field.
     *
     * @see Expr::popLast()
     * @see http://docs.mongodb.org/manual/reference/operator/pop/
     * @return $this
     */
    public function popLast()
    {
        $this->expr->popLast();
        return $this;
    }

    /**
     * Use a primer to eagerly load all references in the current field.
     *
     * If $primer is true or a callable is provided, referenced documents for
     * this field will loaded into UnitOfWork immediately after the query is
     * executed. This will avoid multiple queries due to lazy initialization of
     * Proxy objects.
     *
     * If $primer is false, no priming will take place. That is also the default
     * behavior.
     *
     * If a custom callable is used, its signature should conform to the default
     * Closure defined in {@link ReferencePrimer::__construct()}.
     *
     * @param boolean|callable $primer
     * @return $this
     * @throws \InvalidArgumentException If $primer is not boolean or callable
     */
    public function prime($primer = true)
    {
        if ( ! is_bool($primer) && ! is_callable($primer)) {
            throw new \InvalidArgumentException('$primer is not a boolean or callable');
        }

        if ($primer === false) {
            unset($this->primers[$this->currentField]);

            return $this;
        }

        if (array_key_exists('eagerCursor', $this->query) && !$this->query['eagerCursor']) {
            throw new \BadMethodCallException("Can't call prime() when setting eagerCursor to false");
        }

        $this->primers[$this->currentField] = $primer;
        return $this;
    }

    /**
     * Remove all elements matching the given value or expression from the
     * current array field.
     *
     * @see Expr::pull()
     * @see http://docs.mongodb.org/manual/reference/operator/pull/
     * @param mixed|Expr $valueOrExpression
     * @return $this
     */
    public function pull($valueOrExpression)
    {
        $this->expr->pull($valueOrExpression);
        return $this;
    }

    /**
     * Remove all elements matching any of the given values from the current
     * array field.
     *
     * @see Expr::pullAll()
     * @see http://docs.mongodb.org/manual/reference/operator/pullAll/
     * @param array $values
     * @return $this
     */
    public function pullAll(array $values)
    {
        $this->expr->pullAll($values);
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
     * @see Expr::push()
     * @see http://docs.mongodb.org/manual/reference/operator/push/
     * @see http://docs.mongodb.org/manual/reference/operator/each/
     * @see http://docs.mongodb.org/manual/reference/operator/slice/
     * @see http://docs.mongodb.org/manual/reference/operator/sort/
     * @param mixed|Expr $valueOrExpression
     * @return $this
     */
    public function push($valueOrExpression)
    {
        $this->expr->push($valueOrExpression);
        return $this;
    }

    /**
     * Append multiple values to the current array field.
     *
     * If the field does not exist, it will be set to an array containing the
     * values in the argument. If the field is not an array, the query will
     * yield an error.
     *
     * This operator is deprecated in MongoDB 2.4. {@link Builder::push()} and
     * {@link Expr::each()} should be used in its place.
     *
     * @see Expr::pushAll()
     * @see http://docs.mongodb.org/manual/reference/operator/pushAll/
     * @param array $values
     * @return $this
     */
    public function pushAll(array $values)
    {
        $this->expr->pushAll($values);
        return $this;
    }

    /**
     * Specify $gte and $lt criteria for the current field.
     *
     * This method is shorthand for specifying $gte criteria on the lower bound
     * and $lt criteria on the upper bound. The upper bound is not inclusive.
     *
     * @see Expr::range()
     * @param mixed $start
     * @param mixed $end
     * @return $this
     */
    public function range($start, $end)
    {
        $this->expr->range($start, $end);
        return $this;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function readOnly($bool = true)
    {
        $this->readOnly = $bool;
        return $this;
    }

    /**
     * Set the "reduce" option for a mapReduce or group command.
     *
     * @param string|\MongoCode $reduce
     * @return $this
     * @throws \BadMethodCallException if the query is not a mapReduce or group command
     */
    public function reduce($reduce)
    {
        switch ($this->query['type']) {
            case Query::TYPE_MAP_REDUCE:
                $this->query['mapReduce']['reduce'] = $reduce;
                break;

            case Query::TYPE_GROUP:
                $this->query['group']['reduce'] = $reduce;
                break;

            default:
                throw new \BadMethodCallException('mapReduce(), map() or group() must be called before reduce()');
        }

        return $this;
    }

    /**
     * @param object $document
     * @return $this
     */
    public function references($document)
    {
        $this->expr->references($document);
        return $this;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function refresh($bool = true)
    {
        $this->refresh = $bool;
        return $this;
    }

    /**
     * @param string $documentName
     * @return $this
     */
    public function remove($documentName = null)
    {
        $this->setDocumentName($documentName);
        $this->query['type'] = Query::TYPE_REMOVE;

        return $this;
    }

    /**
     * Rename the current field.
     *
     * @see Expr::rename()
     * @see http://docs.mongodb.org/manual/reference/operator/rename/
     * @param string $name
     * @return $this
     */
    public function rename($name)
    {
        $this->expr->rename($name);
        return $this;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function returnNew($bool = true)
    {
        $this->refresh(true);
        $this->query['new'] = (boolean) $bool;

        return $this;
    }

    /**
     * Set one or more fields to be included in the query projection.
     *
     * @param array|string $fieldName,...
     * @return $this
     */
    public function select($fieldName = null)
    {
        if ( ! isset($this->query['select'])) {
            $this->query['select'] = [];
        }

        $fieldNames = is_array($fieldName) ? $fieldName : func_get_args();

        foreach ($fieldNames as $fieldName) {
            $this->query['select'][$fieldName] = 1;
        }

        return $this;
    }

    /**
     * Select only matching embedded documents in an array field for the query
     * projection.
     *
     * @see http://docs.mongodb.org/manual/reference/projection/elemMatch/
     * @param string $fieldName
     * @param array|Expr $expression
     * @return $this
     */
    public function selectElemMatch($fieldName, $expression)
    {
        if ($expression instanceof Expr) {
            $expression = $expression->getQuery();
        }
        $this->query['select'][$fieldName] = ['$elemMatch' => $expression];
        return $this;
    }

    /**
     * Select a metadata field for the query projection.
     *
     * @see http://docs.mongodb.org/master/reference/operator/projection/meta/
     * @param string $fieldName
     * @param string $metaDataKeyword
     * @return $this
     */
    public function selectMeta($fieldName, $metaDataKeyword)
    {
        $this->query['select'][$fieldName] = ['$meta' => $metaDataKeyword];
        return $this;
    }

    /**
     * Select a slice of an array field for the query projection.
     *
     * The $countOrSkip parameter has two very different meanings, depending on
     * whether or not $limit is provided. See the MongoDB documentation for more
     * information.
     *
     * @see http://docs.mongodb.org/manual/reference/projection/slice/
     * @param string $fieldName
     * @param integer $countOrSkip Count parameter, or skip if limit is specified
     * @param integer $limit       Limit parameter used in conjunction with skip
     * @return $this
     */
    public function selectSlice($fieldName, $countOrSkip, $limit = null)
    {
        $slice = $countOrSkip;
        if ($limit !== null) {
            $slice = [$slice, $limit];
        }
        $this->query['select'][$fieldName] = ['$slice' => $slice];
        return $this;
    }

    /**
     * Set the current field to a value.
     *
     * This is only relevant for insert, update, or findAndUpdate queries. For
     * update and findAndUpdate queries, the $atomic parameter will determine
     * whether or not a $set operator is used.
     *
     * @see Expr::set()
     * @see http://docs.mongodb.org/manual/reference/operator/set/
     * @param mixed $value
     * @param boolean $atomic
     * @return $this
     */
    public function set($value, $atomic = true)
    {
        $this->expr->set($value, $atomic && $this->query['type'] !== Query::TYPE_INSERT);
        return $this;
    }

    /**
     * Set the expression's "new object".
     *
     * @see Expr::setNewObj()
     * @param array $newObj
     * @return $this
     */
    public function setNewObj(array $newObj)
    {
        $this->expr->setNewObj($newObj);
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
     * @see Expr::setOnInsert()
     * @see https://docs.mongodb.org/manual/reference/operator/update/setOnInsert/
     * @param mixed $value
     * @return $this
     */
    public function setOnInsert($value)
    {
        $this->expr->setOnInsert($value);
        return $this;
    }

    /**
     * Set the read preference for the query.
     *
     * This is only relevant for read-only queries and commands.
     *
     * @see http://docs.mongodb.org/manual/core/read-preference/
     * @param ReadPreference $readPreference
     * @return $this
     */
    public function setReadPreference(ReadPreference $readPreference)
    {
        $this->query['readPreference'] = $readPreference;
        return $this;
    }

    /**
     * Set the expression's query criteria.
     *
     * @see Expr::setQuery()
     * @param array $query
     * @return $this
     */
    public function setQueryArray(array $query)
    {
        $this->expr->setQuery($query);
        return $this;
    }

    /**
     * Specify $size criteria for the current field.
     *
     * @see Expr::size()
     * @see http://docs.mongodb.org/manual/reference/operator/size/
     * @param integer $size
     * @return $this
     */
    public function size($size)
    {
        $this->expr->size((integer) $size);
        return $this;
    }

    /**
     * Set the skip for the query cursor.
     *
     * This is only relevant for find queries, or mapReduce queries that store
     * results in an output collecton and return a cursor.
     *
     * @see Query::prepareCursor()
     * @param integer $skip
     * @return $this
     */
    public function skip($skip)
    {
        $this->query['skip'] = (integer) $skip;
        return $this;
    }

    /**
     * Set the snapshot cursor flag.
     *
     * @param boolean $bool
     * @return $this
     */
    public function snapshot($bool = true)
    {
        $this->query['snapshot'] = (boolean) $bool;
        return $this;
    }

    /**
     * Set one or more field/order pairs on which to sort the query.
     *
     * If sorting by multiple fields, the first argument should be an array of
     * field name (key) and order (value) pairs.
     *
     * @param array|string $fieldName Field name or array of field/order pairs
     * @param int|string $order       Field order (if one field is specified)
     * @return $this
     */
    public function sort($fieldName, $order = 1)
    {
        if ( ! isset($this->query['sort'])) {
            $this->query['sort'] = [];
        }

        $fields = is_array($fieldName) ? $fieldName : [$fieldName => $order];

        foreach ($fields as $fieldName => $order) {
            if (is_string($order)) {
                $order = strtolower($order) === 'asc' ? 1 : -1;
            }
            $this->query['sort'][$fieldName] = (integer) $order;
        }

        return $this;
    }

    /**
     * Specify a projected metadata field on which to sort the query.
     *
     * Sort order is not configurable for metadata fields. Sorting by a metadata
     * field requires the same field and $meta expression to exist in the
     * projection document. This method will call {@link Builder::selectMeta()}
     * if the field is not already set in the projection.
     *
     * @see http://docs.mongodb.org/master/reference/operator/projection/meta/#sort
     * @param string $fieldName       Field name of the projected metadata
     * @param string $metaDataKeyword
     * @return $this
     */
    public function sortMeta($fieldName, $metaDataKeyword)
    {
        /* It's possible that the field is already projected without the $meta
         * operator. We'll assume that the user knows what they're doing in that
         * case and will not attempt to override the projection.
         */
        if ( ! isset($this->query['select'][$fieldName])) {
            $this->selectMeta($fieldName, $metaDataKeyword);
        }

        $this->query['sort'][$fieldName] = ['$meta' => $metaDataKeyword];

        return $this;
    }

    /**
     * Set the "spherical" option for a geoNear command query.
     *
     * @param bool $spherical
     * @return $this
     * @throws \BadMethodCallException if the query is not a geoNear command
     */
    public function spherical($spherical = true)
    {
        if ($this->query['type'] !== Query::TYPE_GEO_NEAR) {
            throw new \BadMethodCallException('This method requires a geoNear command (call geoNear() first)');
        }

        $this->query['geoNear']['options']['spherical'] = $spherical;
        return $this;
    }

    /**
     * Specify $text criteria for the current field.
     *
     * The $language option may be set with {@link Builder::language()}.
     *
     * @see Expr::text()
     * @see http://docs.mongodb.org/master/reference/operator/query/text/
     * @param string $search
     * @return $this
     */
    public function text($search)
    {
        $this->expr->text($search);
        return $this;
    }

    /**
     * Specify $type criteria for the current field.
     *
     * @see Expr::type()
     * @see http://docs.mongodb.org/manual/reference/operator/type/
     * @param integer $type
     * @return $this
     */
    public function type($type)
    {
        $this->expr->type($type);
        return $this;
    }

    /**
     * Unset the current field.
     *
     * The field will be removed from the document (not set to null).
     *
     * @see Expr::unsetField()
     * @see http://docs.mongodb.org/manual/reference/operator/unset/
     * @return $this
     */
    public function unsetField()
    {
        $this->expr->unsetField();
        return $this;
    }

    /**
     * @param string $documentName
     * @return $this
     */
    public function updateOne($documentName = null)
    {
        $this->setDocumentName($documentName);
        $this->query['type'] = Query::TYPE_UPDATE;
        $this->query['multiple'] = false;

        return $this;
    }

    /**
     * @param string $documentName
     * @return $this
     */
    public function updateMany($documentName = null)
    {
        $this->setDocumentName($documentName);
        $this->query['type'] = Query::TYPE_UPDATE;
        $this->query['multiple'] = true;

        return $this;
    }

    /**
     * Set the "upsert" option for an update or findAndUpdate query.
     *
     * @param boolean $bool
     * @return $this
     */
    public function upsert($bool = true)
    {
        $this->query['upsert'] = (boolean) $bool;
        return $this;
    }

    /**
     * Specify a JavaScript expression to use for matching documents.
     *
     * @see Expr::where()
     * @see http://docs.mongodb.org/manual/reference/operator/where/
     * @param string|\MongoCode $javascript
     * @return $this
     */
    public function where($javascript)
    {
        $this->expr->where($javascript);
        return $this;
    }

    /**
     * Add $within criteria with a $box shape to the query.
     *
     * @deprecated 1.1 MongoDB 2.4 deprecated $within in favor of $geoWithin
     * @see Builder::geoWithinBox()
     * @see Expr::withinBox()
     * @see http://docs.mongodb.org/manual/reference/operator/box/
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @return $this
     */
    public function withinBox($x1, $y1, $x2, $y2)
    {
        $this->expr->withinBox($x1, $y1, $x2, $y2);
        return $this;
    }

    /**
     * Add $within criteria with a $center shape to the query.
     *
     * @deprecated 1.1 MongoDB 2.4 deprecated $within in favor of $geoWithin
     * @see Builder::geoWithinCenter()
     * @see Expr::withinCenter()
     * @see http://docs.mongodb.org/manual/reference/operator/center/
     * @param float $x
     * @param float $y
     * @param float $radius
     * @return $this
     */
    public function withinCenter($x, $y, $radius)
    {
        $this->expr->withinCenter($x, $y, $radius);
        return $this;
    }

    /**
     * Add $within criteria with a $centerSphere shape to the query.
     *
     * @deprecated 1.1 MongoDB 2.4 deprecated $within in favor of $geoWithin
     * @see Builder::geoWithinCenterSphere()
     * @see Expr::withinCenterSphere()
     * @see http://docs.mongodb.org/manual/reference/operator/centerSphere/
     * @param float $x
     * @param float $y
     * @param float $radius
     * @return $this
     */
    public function withinCenterSphere($x, $y, $radius)
    {
        $this->expr->withinCenterSphere($x, $y, $radius);
        return $this;
    }

    /**
     * Add $within criteria with a $polygon shape to the query.
     *
     * Point coordinates are in x, y order (easting, northing for projected
     * coordinates, longitude, latitude for geographic coordinates).
     *
     * The last point coordinate is implicitly connected with the first.
     *
     * @deprecated 1.1 MongoDB 2.4 deprecated $within in favor of $geoWithin
     * @see Builder::geoWithinPolygon()
     * @see Expr::withinPolygon()
     * @see http://docs.mongodb.org/manual/reference/operator/polygon/
     * @param array $point,... Three or more point coordinate tuples
     * @return $this
     */
    public function withinPolygon(/* array($x1, $y1), array($x2, $y2), ... */)
    {
        $this->expr->withinPolygon(...func_get_args());
        return $this;
    }

    /**
     * Get Discriminator Values
     *
     * @param \Traversable $classNames
     * @return array an array of discriminatorValues (mixed type)
     * @throws \InvalidArgumentException if the number of found collections > 1
     */
    private function getDiscriminatorValues($classNames)
    {
        $discriminatorValues = array();
        $collections = array();
        foreach ($classNames as $className) {
            $class = $this->dm->getClassMetadata($className);
            $discriminatorValues[] = $class->discriminatorValue;
            $key = $this->dm->getDocumentDatabase($className)->getDatabaseName() . '.' . $class->getCollection();
            $collections[$key] = $key;
        }
        if (count($collections) > 1) {
            throw new \InvalidArgumentException('Documents involved are not all mapped to the same database collection.');
        }
        return $discriminatorValues;
    }

    /**
     * @param string[]|string $documentName an array of document names or just one.
     */
    private function setDocumentName($documentName)
    {
        if (is_array($documentName)) {
            $documentNames = $documentName;
            $documentName = $documentNames[0];

            $metadata = $this->dm->getClassMetadata($documentName);
            $discriminatorField = $metadata->discriminatorField;
            $discriminatorValues = $this->getDiscriminatorValues($documentNames);

            // If a defaultDiscriminatorValue is set and it is among the discriminators being queries, add NULL to the list
            if ($metadata->defaultDiscriminatorValue && array_search($metadata->defaultDiscriminatorValue, $discriminatorValues) !== false) {
                $discriminatorValues[] = null;
            }

            $this->field($discriminatorField)->in($discriminatorValues);
        }

        if ($documentName !== null) {
            $this->collection = $this->dm->getDocumentCollection($documentName);
            $this->class = $this->dm->getClassMetadata($documentName);

            // Expr also needs to know
            $this->expr->setClassMetadata($this->class);
        }
    }
}
