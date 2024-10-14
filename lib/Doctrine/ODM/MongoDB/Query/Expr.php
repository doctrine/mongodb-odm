<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Query;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use GeoJson\Geometry\Geometry;
use GeoJson\Geometry\Point;
use InvalidArgumentException;
use LogicException;
use MongoDB\BSON\Binary;
use MongoDB\BSON\Javascript;

use function array_filter;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_values;
use function assert;
use function explode;
use function func_get_args;
use function in_array;
use function is_array;
use function is_string;
use function key;
use function sprintf;
use function strpos;
use function strtolower;

/**
 * Query expression builder for ODM.
 *
 * @phpstan-import-type FieldMapping from ClassMetadata
 */
class Expr
{
    /**
     * The query criteria array.
     */
    private mixed $query = [];

    /**
     * The "new object" array containing either a full document or a number of
     * atomic update operators.
     *
     * @see https://docs.mongodb.org/manual/reference/method/db.collection.update/#update-parameter
     *
     * @var array<string, mixed>
     */
    private array $newObj = [];

    /**
     * The current field we are operating on.
     */
    private ?string $currentField = null;

    /**
     * The DocumentManager instance for this query
     */
    private DocumentManager $dm;

    /**
     * The ClassMetadata instance for the document being queried
     */
    private ?ClassMetadata $class = null;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * Add one or more $and clauses to the current query.
     *
     * @see Builder::addAnd()
     * @see https://docs.mongodb.com/manual/reference/operator/and/
     *
     * @param array<string, mixed>|Expr $expression
     * @param array<string, mixed>|Expr ...$expressions
     */
    public function addAnd($expression, ...$expressions): self
    {
        if (! isset($this->query['$and'])) {
            $this->query['$and'] = [];
        }

        $this->query['$and'] = array_merge(
            $this->query['$and'],
            func_get_args(),
        );

        return $this;
    }

    /**
     * Add one or more $nor clauses to the current query.
     *
     * @see Builder::addNor()
     * @see https://docs.mongodb.com/manual/reference/operator/nor/
     *
     * @param array<string, mixed>|Expr $expression
     * @param array<string, mixed>|Expr ...$expressions
     */
    public function addNor($expression, ...$expressions): self
    {
        if (! isset($this->query['$nor'])) {
            $this->query['$nor'] = [];
        }

        $this->query['$nor'] = array_merge(
            $this->query['$nor'],
            func_get_args(),
        );

        return $this;
    }

    /**
     * Add one or more $or clauses to the current query.
     *
     * @see Builder::addOr()
     * @see https://docs.mongodb.com/manual/reference/operator/or/
     *
     * @param array<string, mixed>|Expr $expression
     * @param array<string, mixed>|Expr ...$expressions
     */
    public function addOr($expression, ...$expressions): self
    {
        if (! isset($this->query['$or'])) {
            $this->query['$or'] = [];
        }

        $this->query['$or'] = array_merge(
            $this->query['$or'],
            func_get_args(),
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
     * @see https://docs.mongodb.com/manual/reference/operator/addToSet/
     * @see https://docs.mongodb.com/manual/reference/operator/each/
     *
     * @param mixed|Expr $valueOrExpression
     */
    public function addToSet($valueOrExpression): self
    {
        $this->requiresCurrentField(__METHOD__);
        $this->newObj['$addToSet'][$this->currentField] = static::convertExpression($valueOrExpression, $this->class);

        return $this;
    }

    /**
     * Specify $all criteria for the current field.
     *
     * @see Builder::all()
     * @see https://docs.mongodb.com/manual/reference/operator/all/
     *
     * @param mixed[] $values
     */
    public function all(array $values): self
    {
        return $this->operator('$all', $values);
    }

    /**
     * Apply a bitwise operation on the current field
     *
     * @see https://docs.mongodb.com/manual/reference/operator/update/bit/
     */
    protected function bit(string $operator, int $value): self
    {
        $this->requiresCurrentField(__METHOD__);
        $this->newObj['$bit'][$this->currentField][$operator] = $value;

        return $this;
    }

    /**
     * Apply a bitwise and operation on the current field.
     *
     * @see Builder::bitAnd()
     * @see https://docs.mongodb.com/manual/reference/operator/update/bit/
     */
    public function bitAnd(int $value): self
    {
        return $this->bit('and', $value);
    }

    /**
     * Apply a bitwise or operation on the current field.
     *
     * @see Builder::bitOr()
     * @see https://docs.mongodb.com/manual/reference/operator/update/bit/
     */
    public function bitOr(int $value): self
    {
        return $this->bit('or', $value);
    }

    /**
     * Matches documents where all of the bit positions given by the query are
     * clear.
     *
     * @see Builder::bitsAllClear()
     * @see https://docs.mongodb.com/manual/reference/operator/query/bitsAllClear/
     *
     * @param int|list<int>|Binary $value
     */
    public function bitsAllClear($value): self
    {
        $this->requiresCurrentField(__METHOD__);

        return $this->operator('$bitsAllClear', $value);
    }

    /**
     * Matches documents where all of the bit positions given by the query are
     * set.
     *
     * @see Builder::bitsAllSet()
     * @see https://docs.mongodb.com/manual/reference/operator/query/bitsAllSet/
     *
     * @param int|list<int>|Binary $value
     */
    public function bitsAllSet($value): self
    {
        $this->requiresCurrentField(__METHOD__);

        return $this->operator('$bitsAllSet', $value);
    }

    /**
     * Matches documents where any of the bit positions given by the query are
     * clear.
     *
     * @see Builder::bitsAnyClear()
     * @see https://docs.mongodb.com/manual/reference/operator/query/bitsAnyClear/
     *
     * @param int|list<int>|Binary $value
     */
    public function bitsAnyClear($value): self
    {
        $this->requiresCurrentField(__METHOD__);

        return $this->operator('$bitsAnyClear', $value);
    }

    /**
     * Matches documents where any of the bit positions given by the query are
     * set.
     *
     * @see Builder::bitsAnySet()
     * @see https://docs.mongodb.com/manual/reference/operator/query/bitsAnySet/
     *
     * @param int|list<int>|Binary $value
     */
    public function bitsAnySet($value): self
    {
        $this->requiresCurrentField(__METHOD__);

        return $this->operator('$bitsAnySet', $value);
    }

    /**
     * Apply a bitwise xor operation on the current field.
     *
     * @see Builder::bitXor()
     * @see https://docs.mongodb.com/manual/reference/operator/update/bit/
     */
    public function bitXor(int $value): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/query/text/
     *
     * @throws BadMethodCallException If the query does not already have $text criteria.
     */
    public function caseSensitive(bool $caseSensitive): self
    {
        if (! isset($this->query['$text'])) {
            throw new BadMethodCallException('This method requires a $text operator (call text() first)');
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
     * @see https://docs.mongodb.com/manual/reference/operator/query/comment/
     */
    public function comment(string $comment): self
    {
        $this->query['$comment'] = $comment;

        return $this;
    }

    /**
     * Sets the value of the current field to the current date, either as a date or a timestamp.
     *
     * @see Builder::currentDate()
     * @see https://docs.mongodb.com/manual/reference/operator/update/currentDate/
     *
     * @throws InvalidArgumentException If an invalid type is given.
     */
    public function currentDate(string $type = 'date'): self
    {
        if (! in_array($type, ['date', 'timestamp'])) {
            throw new InvalidArgumentException('Type for currentDate operator must be date or timestamp.');
        }

        $this->requiresCurrentField(__METHOD__);
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
     * @see https://docs.mongodb.com/manual/reference/operator/query/text/
     *
     * @throws BadMethodCallException If the query does not already have $text criteria.
     */
    public function diacriticSensitive(bool $diacriticSensitive): self
    {
        if (! isset($this->query['$text'])) {
            throw new BadMethodCallException('This method requires a $text operator (call text() first)');
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
     * @see https://docs.mongodb.com/manual/reference/operator/each/
     *
     * @param mixed[] $values
     */
    public function each(array $values): self
    {
        return $this->operator('$each', $values);
    }

    /**
     * Specify $elemMatch criteria for the current field.
     *
     * @see Builder::elemMatch()
     * @see https://docs.mongodb.com/manual/reference/operator/elemMatch/
     *
     * @param array<string, mixed>|Expr $expression
     */
    public function elemMatch($expression): self
    {
        return $this->operator('$elemMatch', $expression);
    }

    /**
     * Specify an equality match for the current field.
     *
     * @see Builder::equals()
     *
     * @param mixed $value
     */
    public function equals($value): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/exists/
     */
    public function exists(bool $bool): self
    {
        return $this->operator('$exists', $bool);
    }

    /**
     * Set the current field for building the expression.
     *
     * @see Builder::field()
     */
    public function field(string $field): self
    {
        $this->currentField = $field;

        return $this;
    }

    /**
     * Add $geoIntersects criteria with a GeoJSON geometry to the expression.
     *
     * The geometry parameter GeoJSON object or an array corresponding to the
     * geometry's JSON representation.
     *
     * @see Builder::geoIntersects()
     * @see https://docs.mongodb.com/manual/reference/operator/geoIntersects/
     *
     * @param array<string, mixed>|Geometry $geometry
     */
    public function geoIntersects($geometry): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/geoIntersects/
     *
     * @param array<string, mixed>|Geometry $geometry
     */
    public function geoWithin($geometry): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/box/
     */
    public function geoWithinBox(float $x1, float $y1, float $x2, float $y2): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/center/
     */
    public function geoWithinCenter(float $x, float $y, float $radius): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/centerSphere/
     */
    public function geoWithinCenterSphere(float $x, float $y, float $radius): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/polygon/
     *
     * @param array{int|float, int|float} $point1    First point of the polygon
     * @param array{int|float, int|float} $point2    Second point of the polygon
     * @param array{int|float, int|float} $point3    Third point of the polygon
     * @param array{int|float, int|float} ...$points Additional points of the polygon
     *
     * @throws InvalidArgumentException If less than three points are given.
     */
    public function geoWithinPolygon($point1, $point2, $point3, ...$points): self
    {
        $shape = ['$polygon' => func_get_args()];

        return $this->operator('$geoWithin', $shape);
    }

    /**
     * Return the current field.
     */
    public function getCurrentField(): ?string
    {
        return $this->currentField;
    }

    /**
     * Gets prepared newObj part of expression.
     *
     * @return array<string, mixed>
     */
    public function getNewObj(): array
    {
        return $this->dm->getUnitOfWork()
            ->getDocumentPersister($this->class->name)
            ->prepareQueryOrNewObj($this->newObj, true);
    }

    /**
     * Gets prepared query part of expression.
     *
     * @return array<string, mixed>
     */
    public function getQuery(): array
    {
        return $this->dm->getUnitOfWork()
            ->getDocumentPersister($this->class->name)
            ->prepareQueryOrNewObj($this->convertExpressions($this->query));
    }

    /**
     * Specify $gt criteria for the current field.
     *
     * @see Builder::gt()
     * @see https://docs.mongodb.com/manual/reference/operator/gt/
     *
     * @param mixed $value
     */
    public function gt($value): self
    {
        return $this->operator('$gt', $value);
    }

    /**
     * Specify $gte criteria for the current field.
     *
     * @see Builder::gte()
     * @see https://docs.mongodb.com/manual/reference/operator/gte/
     *
     * @param mixed $value
     */
    public function gte($value): self
    {
        return $this->operator('$gte', $value);
    }

    /**
     * Specify $in criteria for the current field.
     *
     * @see Builder::in()
     * @see https://docs.mongodb.com/manual/reference/operator/in/
     *
     * @param mixed[] $values
     */
    public function in(array $values): self
    {
        return $this->operator('$in', array_values($values));
    }

    /**
     * Increment the current field.
     *
     * If the field does not exist, it will be set to this value.
     *
     * @see Builder::inc()
     * @see https://docs.mongodb.com/manual/reference/operator/inc/
     *
     * @param float|int $value
     */
    public function inc($value): self
    {
        $this->requiresCurrentField(__METHOD__);
        $this->newObj['$inc'][$this->currentField] = $value;

        return $this;
    }

    /**
     * Checks that the current field includes a reference to the supplied document.
     */
    public function includesReferenceTo(object $document): self
    {
        $this->requiresCurrentField(__METHOD__);
        $mapping   = $this->getReferenceMapping();
        $reference = $this->dm->createReference($document, $mapping);
        $storeAs   = $mapping['storeAs'] ?? null;
        $keys      = [];

        switch ($storeAs) {
            case ClassMetadata::REFERENCE_STORE_AS_ID:
                $this->query[$mapping['name']] = $reference;

                return $this;

            case ClassMetadata::REFERENCE_STORE_AS_REF:
                $keys = ['id' => true];
                break;

            case ClassMetadata::REFERENCE_STORE_AS_DB_REF:
            case ClassMetadata::REFERENCE_STORE_AS_DB_REF_WITH_DB:
                $keys = ['$ref' => true, '$id' => true, '$db' => true];

                if ($storeAs === ClassMetadata::REFERENCE_STORE_AS_DB_REF) {
                    unset($keys['$db']);
                }

                if (isset($mapping['targetDocument'])) {
                    unset($keys['$ref'], $keys['$db']);
                }

                break;

            default:
                throw new InvalidArgumentException(sprintf('Reference type %s is invalid.', $storeAs));
        }

        foreach ($keys as $key => $value) {
            $this->query[$mapping['name']]['$elemMatch'][$key] = $reference[$key];
        }

        return $this;
    }

    /**
     * Set the $language option for $text criteria.
     *
     * This method must be called after text().
     *
     * @see Builder::language()
     * @see https://docs.mongodb.com/manual/reference/operator/query/text/
     *
     * @throws BadMethodCallException If the query does not already have $text criteria.
     */
    public function language(string $language): self
    {
        if (! isset($this->query['$text'])) {
            throw new BadMethodCallException('This method requires a $text operator (call text() first)');
        }

        $this->query['$text']['$language'] = $language;

        return $this;
    }

    /**
     * Specify $lt criteria for the current field.
     *
     * @see Builder::lte()
     * @see https://docs.mongodb.com/manual/reference/operator/lte/
     *
     * @param mixed $value
     */
    public function lt($value): self
    {
        return $this->operator('$lt', $value);
    }

    /**
     * Specify $lte criteria for the current field.
     *
     * @see Builder::lte()
     * @see https://docs.mongodb.com/manual/reference/operator/lte/
     *
     * @param mixed $value
     */
    public function lte($value): self
    {
        return $this->operator('$lte', $value);
    }

    /**
     * Updates the value of the field to a specified value if the specified value is greater than the current value of the field.
     *
     * @see Builder::max()
     * @see https://docs.mongodb.com/manual/reference/operator/update/max/
     *
     * @param mixed $value
     */
    public function max($value): self
    {
        $this->requiresCurrentField(__METHOD__);
        $this->newObj['$max'][$this->currentField] = $value;

        return $this;
    }

    /**
     * Updates the value of the field to a specified value if the specified value is less than the current value of the field.
     *
     * @see Builder::min()
     * @see https://docs.mongodb.com/manual/reference/operator/update/min/
     *
     * @param mixed $value
     */
    public function min($value): self
    {
        $this->requiresCurrentField(__METHOD__);
        $this->newObj['$min'][$this->currentField] = $value;

        return $this;
    }

    /**
     * Specify $mod criteria for the current field.
     *
     * @see Builder::mod()
     * @see https://docs.mongodb.com/manual/reference/operator/mod/
     *
     * @param float|int $divisor
     * @param float|int $remainder
     */
    public function mod($divisor, $remainder = 0): self
    {
        return $this->operator('$mod', [$divisor, $remainder]);
    }

    /**
     * Multiply the current field.
     *
     * If the field does not exist, it will be set to 0.
     *
     * @see Builder::mul()
     * @see https://docs.mongodb.com/manual/reference/operator/update/mul/
     *
     * @param float|int $value
     */
    public function mul($value): self
    {
        $this->requiresCurrentField(__METHOD__);
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
     * @see https://docs.mongodb.com/manual/reference/operator/near/
     *
     * @param float|array<string, mixed>|Point $x
     * @param float                            $y
     */
    public function near($x, $y = null, ?float $minDistance = null, ?float $maxDistance = null): self
    {
        if ($x instanceof Point) {
            $x = $x->jsonSerialize();
        }

        if (is_array($x)) {
            return $this->operator(
                '$near',
                array_filter([
                    '$geometry' => $x,
                    '$minDistance' => $minDistance,
                    '$maxDistance' => $maxDistance,
                ]),
            );
        }

        $this->operator('$near', [$x, $y]);

        if ($minDistance !== null) {
            $this->operator('$minDistance', $minDistance);
        }

        if ($maxDistance !== null) {
            $this->operator('$maxDistance', $maxDistance);
        }

        return $this;
    }

    /**
     * Add $nearSphere criteria to the expression.
     *
     * A GeoJSON point may be provided as the first and only argument for
     * 2dsphere queries. This single parameter may be a GeoJSON point object or
     * an array corresponding to the point's JSON representation.
     *
     * @see Builder::nearSphere()
     * @see https://docs.mongodb.com/manual/reference/operator/nearSphere/
     *
     * @param float|array<string, mixed>|Point $x
     * @param float                            $y
     */
    public function nearSphere($x, $y = null, ?float $minDistance = null, ?float $maxDistance = null): self
    {
        if ($x instanceof Point) {
            $x = $x->jsonSerialize();
        }

        if (is_array($x)) {
            return $this->operator(
                '$nearSphere',
                array_filter([
                    '$geometry' => $x,
                    '$minDistance' => $minDistance,
                    '$maxDistance' => $maxDistance,
                ]),
            );
        }

        $this->operator('$nearSphere', [$x, $y]);

        if ($minDistance !== null) {
            $this->operator('$minDistance', $minDistance);
        }

        if ($maxDistance !== null) {
            $this->operator('$maxDistance', $maxDistance);
        }

        return $this;
    }

    /**
     * Negates an expression for the current field.
     *
     * @see Builder::not()
     * @see https://docs.mongodb.com/manual/reference/operator/not/
     *
     * @param array|Expr|mixed $expression
     */
    public function not($expression): self
    {
        return $this->operator('$not', $expression);
    }

    /**
     * Specify $ne criteria for the current field.
     *
     * @see Builder::notEqual()
     * @see https://docs.mongodb.com/manual/reference/operator/ne/
     *
     * @param mixed $value
     */
    public function notEqual($value): self
    {
        return $this->operator('$ne', $value);
    }

    /**
     * Specify $nin criteria for the current field.
     *
     * @see Builder::notIn()
     * @see https://docs.mongodb.com/manual/reference/operator/nin/
     *
     * @param mixed[] $values
     */
    public function notIn(array $values): self
    {
        return $this->operator('$nin', array_values($values));
    }

    /**
     * Defines an operator and value on the expression.
     *
     * If there is a current field, the operator will be set on it; otherwise,
     * the operator is set at the top level of the query.
     *
     * @param mixed $value
     */
    public function operator(string $operator, $value): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/pop/
     */
    public function popFirst(): self
    {
        $this->requiresCurrentField(__METHOD__);
        $this->newObj['$pop'][$this->currentField] = -1;

        return $this;
    }

    /**
     * Remove the last element from the current array field.
     *
     * @see Builder::popLast()
     * @see https://docs.mongodb.com/manual/reference/operator/pop/
     */
    public function popLast(): self
    {
        $this->requiresCurrentField(__METHOD__);
        $this->newObj['$pop'][$this->currentField] = 1;

        return $this;
    }

    /**
     * Add $position criteria to the expression for a $push operation.
     *
     * This is useful in conjunction with {@link Expr::each()} for a
     * {@link Expr::push()} operation.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/update/position/
     */
    public function position(int $position): self
    {
        return $this->operator('$position', $position);
    }

    /**
     * Remove all elements matching the given value or expression from the
     * current array field.
     *
     * @see Builder::pull()
     * @see https://docs.mongodb.com/manual/reference/operator/pull/
     *
     * @param mixed|Expr $valueOrExpression
     */
    public function pull($valueOrExpression): self
    {
        $this->requiresCurrentField(__METHOD__);
        $this->newObj['$pull'][$this->currentField] = static::convertExpression($valueOrExpression, $this->class);

        return $this;
    }

    /**
     * Remove all elements matching any of the given values from the current
     * array field.
     *
     * @see Builder::pullAll()
     * @see https://docs.mongodb.com/manual/reference/operator/pullAll/
     *
     * @param mixed[] $values
     */
    public function pullAll(array $values): self
    {
        $this->requiresCurrentField(__METHOD__);
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
     * @see https://docs.mongodb.com/manual/reference/operator/push/
     * @see https://docs.mongodb.com/manual/reference/operator/each/
     * @see https://docs.mongodb.com/manual/reference/operator/slice/
     * @see https://docs.mongodb.com/manual/reference/operator/sort/
     *
     * @param mixed|Expr $valueOrExpression
     */
    public function push($valueOrExpression): self
    {
        if ($valueOrExpression instanceof Expr) {
            $valueOrExpression = array_merge(
                ['$each' => []],
                $valueOrExpression->getQuery(),
            );
        }

        $this->requiresCurrentField(__METHOD__);
        $this->newObj['$push'][$this->currentField] = $valueOrExpression;

        return $this;
    }

    /**
     * Specify $gte and $lt criteria for the current field.
     *
     * This method is shorthand for specifying $gte criteria on the lower bound
     * and $lt criteria on the upper bound. The upper bound is not inclusive.
     *
     * @see Builder::range()
     *
     * @param mixed $start
     * @param mixed $end
     */
    public function range($start, $end): self
    {
        return $this->operator('$gte', $start)->operator('$lt', $end);
    }

    /**
     * Checks that the value of the current field is a reference to the supplied document.
     */
    public function references(object $document): self
    {
        $this->requiresCurrentField(__METHOD__);
        $mapping   = $this->getReferenceMapping();
        $reference = $this->dm->createReference($document, $mapping);
        $storeAs   = $mapping['storeAs'] ?? null;
        $keys      = [];

        switch ($storeAs) {
            case ClassMetadata::REFERENCE_STORE_AS_ID:
                $this->query[$mapping['name']] = $reference;

                return $this;

            case ClassMetadata::REFERENCE_STORE_AS_REF:
                $keys = ['id' => true];
                break;

            case ClassMetadata::REFERENCE_STORE_AS_DB_REF:
            case ClassMetadata::REFERENCE_STORE_AS_DB_REF_WITH_DB:
                $keys = ['$ref' => true, '$id' => true, '$db' => true];

                if ($storeAs === ClassMetadata::REFERENCE_STORE_AS_DB_REF) {
                    unset($keys['$db']);
                }

                if (isset($mapping['targetDocument'])) {
                    unset($keys['$ref'], $keys['$db']);
                }

                break;

            default:
                throw new InvalidArgumentException(sprintf('Reference type %s is invalid.', $storeAs));
        }

        foreach ($keys as $key => $value) {
            $this->query[$mapping['name'] . '.' . $key] = $reference[$key];
        }

        return $this;
    }

    /**
     * Rename the current field.
     *
     * @see Builder::rename()
     * @see https://docs.mongodb.com/manual/reference/operator/rename/
     */
    public function rename(string $name): self
    {
        $this->requiresCurrentField(__METHOD__);
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
     * @see https://docs.mongodb.com/manual/reference/operator/set/
     *
     * @param mixed $value
     */
    public function set($value, bool $atomic = true): self
    {
        $this->requiresCurrentField(__METHOD__);
        assert($this->currentField !== null);

        if ($atomic) {
            $this->newObj['$set'][$this->currentField] = $value;

            return $this;
        }

        if (strpos($this->currentField, '.') === false) {
            $this->newObj[$this->currentField] = $value;

            return $this;
        }

        $keys    = explode('.', $this->currentField);
        $current = &$this->newObj;
        foreach ($keys as $key) {
            $current = &$current[$key];
        }

        $current = $value;

        return $this;
    }

    /**
     * Sets ClassMetadata for document being queried.
     */
    public function setClassMetadata(ClassMetadata $class): void
    {
        $this->class = $class;
    }

    /**
     * Set the "new object".
     *
     * @see Builder::setNewObj()
     *
     * @param array<string, mixed> $newObj
     */
    public function setNewObj(array $newObj): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/update/setOnInsert/
     *
     * @param mixed $value
     */
    public function setOnInsert($value): self
    {
        $this->requiresCurrentField(__METHOD__);
        $this->newObj['$setOnInsert'][$this->currentField] = $value;

        return $this;
    }

    /**
     * Set the query criteria.
     *
     * @see Builder::setQueryArray()
     *
     * @param array<string, mixed> $query
     */
    public function setQuery(array $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Specify $size criteria for the current field.
     *
     * @see Builder::size()
     * @see https://docs.mongodb.com/manual/reference/operator/size/
     */
    public function size(int $size): self
    {
        return $this->operator('$size', $size);
    }

    /**
     * Add $slice criteria to the expression for a $push operation.
     *
     * This is useful in conjunction with {@link Expr::each()} for a
     * {@link Expr::push()} operation. {@link Builder::selectSlice()} should be
     * used for specifying $slice for a query projection.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/slice/
     */
    public function slice(int $slice): self
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
     * @see https://docs.mongodb.com/manual/reference/operator/sort/
     *
     * @param array<string, int|string>|string $fieldName Field name or array of field/order pairs
     * @param int|string                       $order     Field order (if one field is specified)
     */
    public function sort($fieldName, $order = null): self
    {
        $fields = is_array($fieldName) ? $fieldName : [$fieldName => $order];

        return $this->operator('$sort', array_map(fn ($order) => $this->normalizeSortOrder($order), $fields));
    }

    /**
     * Specify $text criteria for the current query.
     *
     * The $language option may be set with {@link Expr::language()}.
     *
     * @see Builder::text()
     * @see https://docs.mongodb.com/manual/reference/operator/query/text/
     */
    public function text(string $search): self
    {
        $this->query['$text'] = ['$search' => $search];

        return $this;
    }

    /**
     * Specify $type criteria for the current field.
     *
     * @see Builder::type()
     * @see https://docs.mongodb.com/manual/reference/operator/type/
     *
     * @param int|string $type
     */
    public function type($type): self
    {
        return $this->operator('$type', $type);
    }

    /**
     * Unset the current field.
     *
     * The field will be removed from the document (not set to null).
     *
     * @see Builder::unsetField()
     * @see https://docs.mongodb.com/manual/reference/operator/unset/
     */
    public function unsetField(): self
    {
        $this->requiresCurrentField(__METHOD__);
        $this->newObj['$unset'][$this->currentField] = 1;

        return $this;
    }

    /**
     * Specify a JavaScript expression to use for matching documents.
     *
     * @see Builder::where()
     * @see https://docs.mongodb.com/manual/reference/operator/where/
     *
     * @param string|Javascript $javascript
     */
    public function where($javascript): self
    {
        $this->query['$where'] = $javascript;

        return $this;
    }

    /**
     * Gets reference mapping for current field from current class or its descendants.
     *
     * @return FieldMapping
     *
     * @throws MappingException
     */
    private function getReferenceMapping(): array
    {
        $this->requiresCurrentField(__METHOD__);
        assert($this->currentField !== null);

        try {
            return $this->class->getFieldMapping($this->currentField);
        } catch (MappingException $e) {
            if (empty($this->class->discriminatorMap)) {
                throw $e;
            }

            $mapping = null;
            $foundIn = null;
            foreach ($this->class->discriminatorMap as $child) {
                $childClass = $this->dm->getClassMetadata($child);
                if (! $childClass->hasAssociation($this->currentField)) {
                    continue;
                }

                if ($foundIn !== null && $mapping !== null && $mapping !== $childClass->getFieldMapping($this->currentField)) {
                    throw MappingException::referenceFieldConflict($this->currentField, $foundIn->name, $childClass->name);
                }

                $mapping = $childClass->getFieldMapping($this->currentField);
                $foundIn = $childClass;
            }

            if ($mapping === null) {
                throw MappingException::mappingNotFoundInClassNorDescendants($this->class->name, $this->currentField);
            }

            return $mapping;
        }
    }

    /** @param int|string $order */
    private function normalizeSortOrder($order): int
    {
        if (is_string($order)) {
            $order = strtolower($order) === 'asc' ? 1 : -1;
        }

        return $order;
    }

    /**
     * Ensure that a current field has been set.
     *
     * @throws LogicException If a current field has not been set.
     */
    private function requiresCurrentField(string $method): void
    {
        if (! $this->currentField) {
            throw new LogicException(sprintf('%s requires setting a current field using field().', $method));
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
    private function wrapEqualityCriteria(): void
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
        if (is_array($query)) {
            $key = key($query);

            if (empty($query) || (is_string($key) && strpos($key, '$') === 0)) {
                return;
            }
        }

        $query = ['$in' => [$query]];
    }

    /**
     * @param array<string, mixed>|array<array<string, mixed>> $query
     *
     * @return array<mixed>
     */
    private function convertExpressions(array $query, ?ClassMetadata $classMetadata = null): array
    {
        if ($classMetadata === null) {
            $classMetadata = $this->class;
        }

        $convertedQuery = [];
        foreach ($query as $key => $value) {
            if (is_string($key) && $classMetadata->hasAssociation($key)) {
                $targetDocument = $classMetadata->getAssociationTargetClass($key);

                if ($targetDocument) {
                    $fieldMetadata = $this->dm->getClassMetadata($targetDocument);
                }
            }

            if (is_array($value)) {
                $convertedQuery[$key] = $this->convertExpressions($value, $fieldMetadata ?? $classMetadata);
                continue;
            }

            $convertedQuery[$key] = static::convertExpression($value, $fieldMetadata ?? $classMetadata);
        }

        return $convertedQuery;
    }

    /**
     * Converts expression objects to query arrays. Non-expression values are
     * returned unmodified.
     *
     * @param Expr|mixed $expression
     *
     * @return array<string, mixed>|mixed
     */
    private static function convertExpression($expression, ClassMetadata $classMetadata)
    {
        if (! $expression instanceof Expr) {
            return $expression;
        }

        $expression->setClassMetadata($classMetadata);

        return $expression->getQuery();
    }
}
