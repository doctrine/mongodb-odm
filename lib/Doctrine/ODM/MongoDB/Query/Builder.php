<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Query;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Sort;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Iterator\IterableResult;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use GeoJson\Geometry\Geometry;
use GeoJson\Geometry\Point;
use InvalidArgumentException;
use MongoDB\BSON\Binary;
use MongoDB\BSON\Javascript;
use MongoDB\Collection;
use MongoDB\Driver\ReadPreference;

use function array_filter;
use function array_key_exists;
use function count;
use function func_get_args;
use function in_array;
use function is_array;
use function is_bool;
use function is_callable;
use function is_string;
use function strtolower;

/**
 * Query builder for ODM.
 *
 * @phpstan-import-type QueryShape from Query
 * @phpstan-import-type SortMetaKeywords from Sort
 */
class Builder
{
    /**
     * The DocumentManager instance for this query
     */
    private DocumentManager $dm;

    /**
     * The ClassMetadata instance.
     */
    private ClassMetadata $class;

    /**
     * The current field we are operating on.
     *
     * @todo Change this to private once ODM requires doctrine/mongodb 1.1+
     * @var string
     */
    protected $currentField;

    /**
     * Whether or not to hydrate the data to documents.
     */
    private bool $hydrate = true;

    /**
     * Whether or not to refresh the data for documents that are already in the identity map.
     */
    private bool $refresh = false;

    /**
     * Array of primer Closure instances.
     *
     * @var array<string, true|callable>
     */
    private array $primers = [];

    /**
     * Whether or not to register documents in UnitOfWork.
     */
    private bool $readOnly = false;

    private bool $rewindable = true;

    /**
     * The Collection instance.
     */
    private Collection $collection;

    /**
     * Array containing the query data.
     *
     * @phpstan-var QueryShape
     */
    private array $query = ['type' => Query::TYPE_FIND];

    /**
     * The Expr instance used for building this query.
     *
     * This object includes the query criteria and the "new object" used for
     * insert and update queries.
     */
    private Expr $expr;

    /**
     * Construct a Builder
     *
     * @param string[]|string|null $documentName (optional) an array of document names, the document name, or none
     */
    public function __construct(DocumentManager $dm, $documentName = null)
    {
        $this->dm   = $dm;
        $this->expr = new Expr($dm);
        if ($documentName === null) {
            return;
        }

        $this->setDocumentName($documentName);
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
     * @see https://docs.mongodb.com/manual/reference/operator/and/
     *
     * @param array<string, mixed>|Expr $expression
     * @param array<string, mixed>|Expr ...$expressions
     */
    public function addAnd($expression, ...$expressions): self
    {
        $this->expr->addAnd(...func_get_args());

        return $this;
    }

    /**
     * Add one or more $nor clauses to the current query.
     *
     * You can create a new expression using the {@link Builder::expr()} method.
     *
     * @see Expr::addNor()
     * @see https://docs.mongodb.com/manual/reference/operator/nor/
     *
     * @param array<string, mixed>|Expr $expression
     * @param array<string, mixed>|Expr ...$expressions
     */
    public function addNor($expression, ...$expressions): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/or/
     *
     * @param array<string, mixed>|Expr $expression
     * @param array<string, mixed>|Expr ...$expressions
     */
    public function addOr($expression, ...$expressions): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/addToSet/
     * @see https://docs.mongodb.com/manual/reference/operator/each/
     *
     * @param mixed|Expr $valueOrExpression
     */
    public function addToSet($valueOrExpression): self
    {
        $this->expr->addToSet($valueOrExpression);

        return $this;
    }

    /**
     * Specify $all criteria for the current field.
     *
     * @see Expr::all()
     * @see https://docs.mongodb.com/manual/reference/operator/all/
     *
     * @param mixed[] $values
     */
    public function all(array $values): self
    {
        $this->expr->all($values);

        return $this;
    }

    /**
     * Apply a bitwise and operation on the current field.
     *
     * @see Expr::bitAnd()
     * @see https://docs.mongodb.com/manual/reference/operator/update/bit/
     *
     * @return $this
     */
    public function bitAnd(int $value): self
    {
        $this->expr->bitAnd($value);

        return $this;
    }

    /**
     * Apply a bitwise or operation on the current field.
     *
     * @see Expr::bitOr()
     * @see https://docs.mongodb.com/manual/reference/operator/update/bit/
     */
    public function bitOr(int $value): self
    {
        $this->expr->bitOr($value);

        return $this;
    }

    /**
     * Matches documents where all of the bit positions given by the query are
     * clear.
     *
     * @see Expr::bitsAllClear()
     * @see https://docs.mongodb.com/manual/reference/operator/query/bitsAllClear/
     *
     * @param int|list<int>|Binary $value
     */
    public function bitsAllClear($value): self
    {
        $this->expr->bitsAllClear($value);

        return $this;
    }

    /**
     * Matches documents where all of the bit positions given by the query are
     * set.
     *
     * @see Expr::bitsAllSet()
     * @see https://docs.mongodb.com/manual/reference/operator/query/bitsAllSet/
     *
     * @param int|list<int>|Binary $value
     */
    public function bitsAllSet($value): self
    {
        $this->expr->bitsAllSet($value);

        return $this;
    }

    /**
     * Matches documents where any of the bit positions given by the query are
     * clear.
     *
     * @see Expr::bitsAnyClear()
     * @see https://docs.mongodb.com/manual/reference/operator/query/bitsAnyClear/
     *
     * @param int|list<int>|Binary $value
     */
    public function bitsAnyClear($value): self
    {
        $this->expr->bitsAnyClear($value);

        return $this;
    }

    /**
     * Matches documents where any of the bit positions given by the query are
     * set.
     *
     * @see Expr::bitsAnySet()
     * @see https://docs.mongodb.com/manual/reference/operator/query/bitsAnySet/
     *
     * @param int|list<int>|Binary $value
     */
    public function bitsAnySet($value): self
    {
        $this->expr->bitsAnySet($value);

        return $this;
    }

    /**
     * Apply a bitwise xor operation on the current field.
     *
     * @see Expr::bitXor()
     * @see https://docs.mongodb.com/manual/reference/operator/update/bit/
     */
    public function bitXor(int $value): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/query/text/
     *
     * @throws BadMethodCallException If the query does not already have $text criteria.
     */
    public function caseSensitive(bool $caseSensitive): self
    {
        $this->expr->caseSensitive($caseSensitive);

        return $this;
    }

    /**
     * Associates a comment to any expression taking a query predicate.
     *
     * @see Expr::comment()
     * @see https://docs.mongodb.com/manual/reference/operator/query/comment/
     */
    public function comment(string $comment): self
    {
        $this->expr->comment($comment);

        return $this;
    }

    /**
     * Change the query type to count.
     */
    public function count(): self
    {
        $this->query['type'] = Query::TYPE_COUNT;

        return $this;
    }

    /**
     * Sets the value of the current field to the current date, either as a date or a timestamp.
     *
     * @see Expr::currentDate()
     * @see https://docs.mongodb.com/manual/reference/operator/update/currentDate/
     */
    public function currentDate(string $type = 'date'): self
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
     * @return mixed
     */
    public function debug(?string $name = null)
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
     * @see https://docs.mongodb.com/manual/reference/operator/query/text/
     *
     * @throws BadMethodCallException If the query does not already have $text criteria.
     */
    public function diacriticSensitive(bool $diacriticSensitive): self
    {
        $this->expr->diacriticSensitive($diacriticSensitive);

        return $this;
    }

    /**
     * Change the query type to a distinct command.
     *
     * @see https://docs.mongodb.com/manual/reference/command/distinct/
     */
    public function distinct(string $field): self
    {
        $this->query['type']     = Query::TYPE_DISTINCT;
        $this->query['distinct'] = $field;

        return $this;
    }

    /**
     * Specify $elemMatch criteria for the current field.
     *
     * You can create a new expression using the {@link Builder::expr()} method.
     *
     * @see Expr::elemMatch()
     * @see https://docs.mongodb.com/manual/reference/operator/elemMatch/
     *
     * @param array<string, mixed>|Expr $expression
     */
    public function elemMatch($expression): self
    {
        $this->expr->elemMatch($expression);

        return $this;
    }

    /**
     * Specify an equality match for the current field.
     *
     * @see Expr::equals()
     *
     * @param mixed $value
     */
    public function equals($value): self
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
     * @param string[]|string $fieldName,...
     */
    public function exclude($fieldName = null): self
    {
        if (! isset($this->query['select'])) {
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
     * @see https://docs.mongodb.com/manual/reference/operator/exists/
     */
    public function exists(bool $bool): self
    {
        $this->expr->exists($bool);

        return $this;
    }

    /**
     * Create a new Expr instance that can be used as an expression with the Builder
     */
    public function expr(): Expr
    {
        $expr = new Expr($this->dm);
        $expr->setClassMetadata($this->class);

        return $expr;
    }

    /**
     * Set the current field to operate on.
     */
    public function field(string $field): self
    {
        $this->currentField = $field;
        $this->expr->field($field);

        return $this;
    }

    /**
     * Change the query type to find and optionally set and change the class being queried.
     */
    public function find(?string $documentName = null): self
    {
        $this->setDocumentName($documentName);
        $this->query['type'] = Query::TYPE_FIND;

        return $this;
    }

    public function findAndRemove(?string $documentName = null): self
    {
        $this->setDocumentName($documentName);
        $this->query['type'] = Query::TYPE_FIND_AND_REMOVE;

        return $this;
    }

    public function findAndUpdate(?string $documentName = null): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/geoIntersects/
     *
     * @param array<string, mixed>|Geometry $geometry
     */
    public function geoIntersects($geometry): self
    {
        $this->expr->geoIntersects($geometry);

        return $this;
    }

    /**
     * Add $geoWithin criteria with a GeoJSON geometry to the query.
     *
     * The geometry parameter GeoJSON object or an array corresponding to the
     * geometry's JSON representation.
     *
     * @see Expr::geoWithin()
     * @see https://docs.mongodb.com/manual/reference/operator/geoWithin/
     *
     * @param array<string, mixed>|Geometry $geometry
     */
    public function geoWithin($geometry): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/box/
     */
    public function geoWithinBox(float $x1, float $y1, float $x2, float $y2): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/center/
     */
    public function geoWithinCenter(float $x, float $y, float $radius): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/centerSphere/
     */
    public function geoWithinCenterSphere(float $x, float $y, float $radius): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/polygon/
     *
     * @param array{int|float, int|float} $point1    First point of the polygon
     * @param array{int|float, int|float} $point2    Second point of the polygon
     * @param array{int|float, int|float} $point3    Third point of the polygon
     * @param array{int|float, int|float} ...$points Additional points of the polygon
     */
    public function geoWithinPolygon($point1, $point2, $point3, ...$points): self
    {
        $this->expr->geoWithinPolygon(...func_get_args());

        return $this;
    }

    /**
     * Return the expression's "new object".
     *
     * @see Expr::getNewObj()
     *
     * @return array<string, mixed>
     */
    public function getNewObj(): array
    {
        return $this->expr->getNewObj();
    }

    /**
     * Gets the Query executable.
     *
     * @param array<string, mixed> $options
     *
     * @return Query
     */
    public function getQuery(array $options = []): IterableResult
    {
        $documentPersister = $this->dm->getUnitOfWork()->getDocumentPersister($this->class->name);

        $query = $this->query;

        $query['query'] = $this->expr->getQuery();
        $query['query'] = $documentPersister->addDiscriminatorToPreparedQuery($query['query']);
        $query['query'] = $documentPersister->addFilterToPreparedQuery($query['query']);

        $query['newObj'] = $this->expr->getNewObj();

        if (isset($query['distinct'])) {
            $query['distinct'] = $documentPersister->prepareFieldName($query['distinct']);
        }

        if (
            $this->class->inheritanceType === ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION && ! empty($query['upsert']) &&
            (empty($query['query'][$this->class->discriminatorField]) || is_array($query['query'][$this->class->discriminatorField]))
        ) {
            throw new InvalidArgumentException('Upsert query that is to be performed on discriminated document does not have single ' .
                'discriminator. Either not use base class or set \'' . $this->class->discriminatorField . '\' field manually.');
        }

        if (! empty($query['select'])) {
            $query['select'] = $documentPersister->prepareProjection($query['select']);
            if (
                $this->hydrate && $this->class->inheritanceType === ClassMetadata::INHERITANCE_TYPE_SINGLE_COLLECTION
                && ! isset($query['select'][$this->class->discriminatorField])
            ) {
                $includeMode = 0 < count(array_filter($query['select'], static fn ($mode) => $mode === 1));
                if ($includeMode) {
                    $query['select'][$this->class->discriminatorField] = 1;
                }
            }
        }

        if (isset($query['sort'])) {
            $query['sort'] = $documentPersister->prepareSort($query['sort']);
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
            $this->readOnly,
            $this->rewindable,
        );
    }

    /**
     * Return the expression's query criteria.
     *
     * @see Expr::getQuery()
     *
     * @return array<string, mixed>
     */
    public function getQueryArray(): array
    {
        return $this->expr->getQuery();
    }

    /**
     * Get the type of this query.
     */
    public function getType(): int
    {
        return $this->query['type'];
    }

    /**
     * Specify $gt criteria for the current field.
     *
     * @see Expr::gt()
     * @see https://docs.mongodb.com/manual/reference/operator/gt/
     *
     * @param mixed $value
     */
    public function gt($value): self
    {
        $this->expr->gt($value);

        return $this;
    }

    /**
     * Specify $gte criteria for the current field.
     *
     * @see Expr::gte()
     * @see https://docs.mongodb.com/manual/reference/operator/gte/
     *
     * @param mixed $value
     */
    public function gte($value): self
    {
        $this->expr->gte($value);

        return $this;
    }

    /**
     * Set the index hint for the query.
     *
     * @param array<string, -1|1>|string $index
     */
    public function hint($index): self
    {
        $this->query['hint'] = $index;

        return $this;
    }

    public function hydrate(bool $bool = true): self
    {
        $this->hydrate = $bool;

        return $this;
    }

    /**
     * Set the immortal cursor flag.
     */
    public function immortal(bool $bool = true): self
    {
        $this->query['immortal'] = $bool;

        return $this;
    }

    /**
     * Specify $in criteria for the current field.
     *
     * @see Expr::in()
     * @see https://docs.mongodb.com/manual/reference/operator/in/
     *
     * @param mixed[] $values
     */
    public function in(array $values): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/inc/
     *
     * @param float|int $value
     */
    public function inc($value): self
    {
        $this->expr->inc($value);

        return $this;
    }

    public function includesReferenceTo(object $document): self
    {
        $this->expr->includesReferenceTo($document);

        return $this;
    }

    public function insert(?string $documentName = null): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/query/text/
     */
    public function language(string $language): self
    {
        $this->expr->language($language);

        return $this;
    }

    /**
     * Set the limit for the query.
     *
     * This is only relevant for find queries and count commands.
     *
     * @see Query::prepareCursor()
     */
    public function limit(int $limit): self
    {
        $this->query['limit'] = $limit;

        return $this;
    }

    /**
     * Specify $lt criteria for the current field.
     *
     * @see Expr::lte()
     * @see https://docs.mongodb.com/manual/reference/operator/lte/
     *
     * @param mixed $value
     */
    public function lt($value): self
    {
        $this->expr->lt($value);

        return $this;
    }

    /**
     * Specify $lte criteria for the current field.
     *
     * @see Expr::lte()
     * @see https://docs.mongodb.com/manual/reference/operator/lte/
     *
     * @param mixed $value
     */
    public function lte($value): self
    {
        $this->expr->lte($value);

        return $this;
    }

    /**
     * Updates the value of the field to a specified value if the specified value is greater than the current value of the field.
     *
     * @see Expr::max()
     * @see https://docs.mongodb.com/manual/reference/operator/update/max/
     *
     * @param mixed $value
     */
    public function max($value): self
    {
        $this->expr->max($value);

        return $this;
    }

    /**
     * Specifies a cumulative time limit in milliseconds for processing operations on a cursor.
     */
    public function maxTimeMS(int $ms): self
    {
        $this->query['maxTimeMS'] = $ms;

        return $this;
    }

    /**
     * Updates the value of the field to a specified value if the specified value is less than the current value of the field.
     *
     * @see Expr::min()
     * @see https://docs.mongodb.com/manual/reference/operator/update/min/
     *
     * @param mixed $value
     */
    public function min($value): self
    {
        $this->expr->min($value);

        return $this;
    }

    /**
     * Specify $mod criteria for the current field.
     *
     * @see Expr::mod()
     * @see https://docs.mongodb.com/manual/reference/operator/mod/
     *
     * @param float|int $divisor
     * @param float|int $remainder
     */
    public function mod($divisor, $remainder = 0): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/update/mul/
     *
     * @param float|int $value
     */
    public function mul($value): self
    {
        $this->expr->mul($value);

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
     * @see https://docs.mongodb.com/manual/reference/operator/near/
     *
     * @param float|array<string, mixed>|Point $x
     * @param float                            $y
     */
    public function near($x, $y = null, ?float $minDistance = null, ?float $maxDistance = null): self
    {
        $this->expr->near($x, $y, $minDistance, $maxDistance);

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
     * @see https://docs.mongodb.com/manual/reference/operator/nearSphere/
     *
     * @param float|array<string, mixed>|Point $x
     * @param float                            $y
     */
    public function nearSphere($x, $y = null, ?float $minDistance = null, ?float $maxDistance = null): self
    {
        $this->expr->nearSphere($x, $y, $minDistance, $maxDistance);

        return $this;
    }

    /**
     * Negates an expression for the current field.
     *
     * You can create a new expression using the {@link Builder::expr()} method.
     *
     * @see Expr::not()
     * @see https://docs.mongodb.com/manual/reference/operator/not/
     *
     * @param array|Expr|mixed $valueOrExpression
     */
    public function not($valueOrExpression): self
    {
        $this->expr->not($valueOrExpression);

        return $this;
    }

    /**
     * Specify $ne criteria for the current field.
     *
     * @see Expr::notEqual()
     * @see https://docs.mongodb.com/manual/reference/operator/ne/
     *
     * @param mixed $value
     */
    public function notEqual($value): self
    {
        $this->expr->notEqual($value);

        return $this;
    }

    /**
     * Specify $nin criteria for the current field.
     *
     * @see Expr::notIn()
     * @see https://docs.mongodb.com/manual/reference/operator/nin/
     *
     * @param mixed[] $values
     */
    public function notIn(array $values): self
    {
        $this->expr->notIn($values);

        return $this;
    }

    /**
     * Remove the first element from the current array field.
     *
     * @see Expr::popFirst()
     * @see https://docs.mongodb.com/manual/reference/operator/pop/
     */
    public function popFirst(): self
    {
        $this->expr->popFirst();

        return $this;
    }

    /**
     * Remove the last element from the current array field.
     *
     * @see Expr::popLast()
     * @see https://docs.mongodb.com/manual/reference/operator/pop/
     */
    public function popLast(): self
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
     * @param bool|callable $primer
     *
     * @throws InvalidArgumentException If $primer is not boolean or callable.
     */
    public function prime($primer = true): self
    {
        if (! is_bool($primer) && ! is_callable($primer)) {
            throw new InvalidArgumentException('$primer is not a boolean or callable');
        }

        if ($primer === false) {
            unset($this->primers[$this->currentField]);

            return $this;
        }

        $this->primers[$this->currentField] = $primer;

        return $this;
    }

    /**
     * Remove all elements matching the given value or expression from the
     * current array field.
     *
     * @see Expr::pull()
     * @see https://docs.mongodb.com/manual/reference/operator/pull/
     *
     * @param mixed|Expr $valueOrExpression
     */
    public function pull($valueOrExpression): self
    {
        $this->expr->pull($valueOrExpression);

        return $this;
    }

    /**
     * Remove all elements matching any of the given values from the current
     * array field.
     *
     * @see Expr::pullAll()
     * @see https://docs.mongodb.com/manual/reference/operator/pullAll/
     *
     * @param mixed[] $values
     */
    public function pullAll(array $values): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/push/
     * @see https://docs.mongodb.com/manual/reference/operator/each/
     * @see https://docs.mongodb.com/manual/reference/operator/slice/
     * @see https://docs.mongodb.com/manual/reference/operator/sort/
     *
     * @param mixed|Expr $valueOrExpression
     */
    public function push($valueOrExpression): self
    {
        $this->expr->push($valueOrExpression);

        return $this;
    }

    /**
     * Specify $gte and $lt criteria for the current field.
     *
     * This method is shorthand for specifying $gte criteria on the lower bound
     * and $lt criteria on the upper bound. The upper bound is not inclusive.
     *
     * @see Expr::range()
     *
     * @param mixed $start
     * @param mixed $end
     */
    public function range($start, $end): self
    {
        $this->expr->range($start, $end);

        return $this;
    }

    public function readOnly(bool $bool = true): self
    {
        $this->readOnly = $bool;

        return $this;
    }

    public function references(object $document): self
    {
        $this->expr->references($document);

        return $this;
    }

    public function refresh(bool $bool = true): self
    {
        $this->refresh = $bool;

        return $this;
    }

    public function remove(?string $documentName = null): self
    {
        $this->setDocumentName($documentName);
        $this->query['type'] = Query::TYPE_REMOVE;

        return $this;
    }

    /**
     * Rename the current field.
     *
     * @see Expr::rename()
     * @see https://docs.mongodb.com/manual/reference/operator/rename/
     */
    public function rename(string $name): self
    {
        $this->expr->rename($name);

        return $this;
    }

    public function returnNew(bool $bool = true): self
    {
        $this->refresh(true);
        $this->query['new'] = $bool;

        return $this;
    }

    /**
     * Set one or more fields to be included in the query projection.
     *
     * @param string[]|string $fieldName,...
     */
    public function select($fieldName = null): self
    {
        if (! isset($this->query['select'])) {
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
     * @see https://docs.mongodb.com/manual/reference/projection/elemMatch/
     *
     * @param array<string, mixed>|Expr $expression
     */
    public function selectElemMatch(string $fieldName, $expression): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/projection/meta/
     */
    public function selectMeta(string $fieldName, string $metaDataKeyword): self
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
     * @see https://docs.mongodb.com/manual/reference/projection/slice/
     */
    public function selectSlice(string $fieldName, int $countOrSkip, ?int $limit = null): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/set/
     *
     * @param mixed $value
     */
    public function set($value, bool $atomic = true): self
    {
        $this->expr->set($value, $atomic && $this->query['type'] !== Query::TYPE_INSERT);

        return $this;
    }

    /**
     * Set the expression's "new object".
     *
     * @see Expr::setNewObj()
     *
     * @param array<string, mixed> $newObj
     */
    public function setNewObj(array $newObj): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/update/setOnInsert/
     *
     * @param mixed $value
     */
    public function setOnInsert($value): self
    {
        $this->expr->setOnInsert($value);

        return $this;
    }

    /**
     * Set the read preference for the query.
     *
     * This is only relevant for read-only queries and commands.
     *
     * @see https://docs.mongodb.com/manual/core/read-preference/
     */
    public function setReadPreference(ReadPreference $readPreference): self
    {
        $this->query['readPreference'] = $readPreference;

        return $this;
    }

    public function setRewindable(bool $rewindable = true): self
    {
        $this->rewindable = $rewindable;

        return $this;
    }

    /**
     * Set the expression's query criteria.
     *
     * @see Expr::setQuery()
     *
     * @param array<string, mixed> $query
     */
    public function setQueryArray(array $query): self
    {
        $this->expr->setQuery($query);

        return $this;
    }

    /**
     * Specify $size criteria for the current field.
     *
     * @see Expr::size()
     * @see https://docs.mongodb.com/manual/reference/operator/size/
     */
    public function size(int $size): self
    {
        $this->expr->size($size);

        return $this;
    }

    /**
     * Set the skip for the query cursor.
     *
     * This is only relevant for find queries, or mapReduce queries that store
     * results in an output collection and return a cursor.
     *
     * @see Query::prepareCursor()
     */
    public function skip(int $skip): self
    {
        $this->query['skip'] = $skip;

        return $this;
    }

    /**
     * Set the snapshot cursor flag.
     */
    public function snapshot(bool $bool = true): self
    {
        $this->query['snapshot'] = $bool;

        return $this;
    }

    /**
     * Set one or more field/order pairs on which to sort the query.
     *
     * If sorting by multiple fields, the first argument should be an array of
     * field name (key) and order (value) pairs.
     *
     * @param array<string, int|string>|string $fieldName Field name or array of field/order pairs
     * @param int|string                       $order     Field order (if one field is specified)
     */
    public function sort($fieldName, $order = 1): self
    {
        if (! isset($this->query['sort'])) {
            $this->query['sort'] = [];
        }

        $fields = is_array($fieldName) ? $fieldName : [$fieldName => $order];

        foreach ($fields as $fieldName => $order) {
            if (is_string($order)) {
                $order = strtolower($order) === 'asc' ? 1 : -1;
            }

            $this->query['sort'][$fieldName] = (int) $order;
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
     * @see https://docs.mongodb.com/manual/reference/operator/projection/meta/#sort
     *
     * @phpstan-param SortMetaKeywords $metaDataKeyword
     */
    public function sortMeta(string $fieldName, string $metaDataKeyword): self
    {
        /* It's possible that the field is already projected without the $meta
         * operator. We'll assume that the user knows what they're doing in that
         * case and will not attempt to override the projection.
         */
        if (! isset($this->query['select'][$fieldName])) {
            $this->selectMeta($fieldName, $metaDataKeyword);
        }

        $this->query['sort'][$fieldName] = ['$meta' => $metaDataKeyword];

        return $this;
    }

    /**
     * Specify $text criteria for the current field.
     *
     * The $language option may be set with {@link Builder::language()}.
     *
     * @see Expr::text()
     * @see https://docs.mongodb.com/manual/reference/operator/query/text/
     */
    public function text(string $search): self
    {
        $this->expr->text($search);

        return $this;
    }

    /**
     * Specify $type criteria for the current field.
     *
     * @see Expr::type()
     * @see https://docs.mongodb.com/manual/reference/operator/type/
     *
     * @param int|string $type
     */
    public function type($type): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/unset/
     */
    public function unsetField(): self
    {
        $this->expr->unsetField();

        return $this;
    }

    public function updateOne(?string $documentName = null): self
    {
        $this->setDocumentName($documentName);
        $this->query['type']     = Query::TYPE_UPDATE;
        $this->query['multiple'] = false;

        return $this;
    }

    public function updateMany(?string $documentName = null): self
    {
        $this->setDocumentName($documentName);
        $this->query['type']     = Query::TYPE_UPDATE;
        $this->query['multiple'] = true;

        return $this;
    }

    /**
     * Set the "upsert" option for an update or findAndUpdate query.
     */
    public function upsert(bool $bool = true): self
    {
        $this->query['upsert'] = $bool;

        return $this;
    }

    /**
     * Specify a JavaScript expression to use for matching documents.
     *
     * @see Expr::where()
     * @see https://docs.mongodb.com/manual/reference/operator/where/
     *
     * @param string|Javascript $javascript
     */
    public function where($javascript): self
    {
        $this->expr->where($javascript);

        return $this;
    }

    /**
     * Get Discriminator Values
     *
     * @param class-string[] $classNames
     *
     * @return array<string|null>
     *
     * @throws InvalidArgumentException If the number of found collections > 1.
     */
    private function getDiscriminatorValues(array $classNames): array
    {
        $discriminatorValues = [];
        $collections         = [];
        foreach ($classNames as $className) {
            $class                 = $this->dm->getClassMetadata($className);
            $discriminatorValues[] = $class->discriminatorValue;
            $key                   = $this->dm->getDocumentDatabase($className)->getDatabaseName() . '.' . $class->getCollection();
            $collections[$key]     = $key;
        }

        if (count($collections) > 1) {
            throw new InvalidArgumentException('Documents involved are not all mapped to the same database collection.');
        }

        return $discriminatorValues;
    }

    /** @param class-string[]|class-string|null $documentName an array of document names or just one. */
    private function setDocumentName($documentName): void
    {
        if (is_array($documentName)) {
            $documentNames = $documentName;
            $documentName  = $documentNames[0];

            $metadata            = $this->dm->getClassMetadata($documentName);
            $discriminatorField  = $metadata->discriminatorField ?? ClassMetadata::DEFAULT_DISCRIMINATOR_FIELD;
            $discriminatorValues = $this->getDiscriminatorValues($documentNames);

            // If a defaultDiscriminatorValue is set and it is among the discriminators being queries, add NULL to the list
            if ($metadata->defaultDiscriminatorValue && in_array($metadata->defaultDiscriminatorValue, $discriminatorValues)) {
                $discriminatorValues[] = null;
            }

            $this->field($discriminatorField)->in($discriminatorValues);
        }

        if ($documentName === null) {
            return;
        }

        $this->collection = $this->dm->getDocumentCollection($documentName);
        $this->class      = $this->dm->getClassMetadata($documentName);

        // Expr also needs to know
        $this->expr->setClassMetadata($this->class);
    }
}
