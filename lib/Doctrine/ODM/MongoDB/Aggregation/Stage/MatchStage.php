<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Query\Expr;
use GeoJson\Geometry\Geometry;

use function func_get_args;

/**
 * Fluent interface for building aggregation pipelines.
 */
class MatchStage extends Stage
{
    /** @var Expr */
    protected $query;

    public function __construct(Builder $builder)
    {
        parent::__construct($builder);

        $this->query = $this->expr();
    }

    public function __clone()
    {
        $this->query = clone $this->query;
    }

    /**
     * Add one or more $and clauses to the current query.
     *
     * You can create a new expression using the {@link Builder::matchExpr()}
     * method.
     *
     * @see Expr::addAnd()
     * @see https://docs.mongodb.com/manual/reference/operator/and/
     *
     * @param mixed[]|Expr $expression
     * @param mixed[]|Expr ...$expressions
     */
    public function addAnd($expression, ...$expressions): static
    {
        $this->query->addAnd(...func_get_args());

        return $this;
    }

    /**
     * Add one or more $nor clauses to the current query.
     *
     * You can create a new expression using the {@link Builder::matchExpr()}
     * method.
     *
     * @see Expr::addNor()
     * @see https://docs.mongodb.com/manual/reference/operator/nor/
     *
     * @param mixed[]|Expr $expression
     * @param mixed[]|Expr ...$expressions
     */
    public function addNor($expression, ...$expressions): static
    {
        $this->query->addNor(...func_get_args());

        return $this;
    }

    /**
     * Add one or more $or clauses to the current query.
     *
     * You can create a new expression using the {@link Builder::matchExpr()}
     * method.
     *
     * @see Expr::addOr()
     * @see https://docs.mongodb.com/manual/reference/operator/or/
     *
     * @param mixed[]|Expr $expression
     * @param mixed[]|Expr ...$expressions
     */
    public function addOr($expression, ...$expressions): static
    {
        $this->query->addOr(...func_get_args());

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
    public function all(array $values): static
    {
        $this->query->all($values);

        return $this;
    }

    /**
     * Specify $elemMatch criteria for the current field.
     *
     * You can create a new expression using the {@link Builder::matchExpr()}
     * method.
     *
     * @see Expr::elemMatch()
     * @see https://docs.mongodb.com/manual/reference/operator/elemMatch/
     *
     * @param mixed[]|Expr $expression
     */
    public function elemMatch($expression): static
    {
        $this->query->elemMatch($expression);

        return $this;
    }

    /**
     * Specify an equality match for the current field.
     *
     * @see Expr::equals()
     *
     * @param mixed $value
     */
    public function equals($value): static
    {
        $this->query->equals($value);

        return $this;
    }

    /**
     * Specify $exists criteria for the current field.
     *
     * @see Expr::exists()
     * @see https://docs.mongodb.com/manual/reference/operator/exists/
     */
    public function exists(bool $bool): static
    {
        $this->query->exists($bool);

        return $this;
    }

    /**
     * Create a new Expr instance that can be used to build partial expressions
     * for other operator methods.
     */
    public function expr(): Expr
    {
        return $this->builder->matchExpr();
    }

    /**
     * Set the current field for building the expression.
     *
     * @see Expr::field()
     */
    public function field(string $field): static
    {
        $this->query->field($field);

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
    public function geoIntersects($geometry): static
    {
        $this->query->geoIntersects($geometry);

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
     */
    public function geoWithin(Geometry $geometry): static
    {
        $this->query->geoWithin($geometry);

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
    public function geoWithinBox(float $x1, float $y1, float $x2, float $y2): static
    {
        $this->query->geoWithinBox($x1, $y1, $x2, $y2);

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
    public function geoWithinCenter(float $x, float $y, float $radius): static
    {
        $this->query->geoWithinCenter($x, $y, $radius);

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
    public function geoWithinCenterSphere(float $x, float $y, float $radius): static
    {
        $this->query->geoWithinCenterSphere($x, $y, $radius);

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
    public function geoWithinPolygon($point1, $point2, $point3, ...$points): static
    {
        $this->query->geoWithinPolygon(...func_get_args());

        return $this;
    }

    public function getExpression(): array
    {
        return [
            '$match' => $this->query->getQuery() ?: (object) [],
        ];
    }

    /**
     * Specify $gt criteria for the current field.
     *
     * @see Expr::gt()
     * @see https://docs.mongodb.com/manual/reference/operator/gt/
     *
     * @param mixed $value
     */
    public function gt($value): static
    {
        $this->query->gt($value);

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
    public function gte($value): static
    {
        $this->query->gte($value);

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
    public function in(array $values): static
    {
        $this->query->in($values);

        return $this;
    }

    public function includesReferenceTo(object $document): static
    {
        $this->query->includesReferenceTo($document);

        return $this;
    }

    /**
     * Set the $language option for $text criteria.
     *
     * This method must be called after text().
     *
     * @see Expr::language()
     * @see https://docs.mongodb.com/manual/reference/operator/text/
     */
    public function language(string $language): static
    {
        $this->query->language($language);

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
    public function lt($value): static
    {
        $this->query->lt($value);

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
    public function lte($value): static
    {
        $this->query->lte($value);

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
    public function mod($divisor, $remainder = 0): static
    {
        $this->query->mod($divisor, $remainder);

        return $this;
    }

    /**
     * Negates an expression for the current field.
     *
     * You can create a new expression using the {@link Builder::matchExpr()}
     * method.
     *
     * @see Expr::not()
     * @see https://docs.mongodb.com/manual/reference/operator/not/
     *
     * @param mixed[]|Expr $expression
     */
    public function not($expression): static
    {
        $this->query->not($expression);

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
    public function notEqual($value): static
    {
        $this->query->notEqual($value);

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
    public function notIn(array $values): static
    {
        $this->query->notIn($values);

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
    public function range($start, $end): static
    {
        $this->query->range($start, $end);

        return $this;
    }

    public function references(object $document): static
    {
        $this->query->references($document);

        return $this;
    }

    /**
     * Specify $size criteria for the current field.
     *
     * @see Expr::size()
     * @see https://docs.mongodb.com/manual/reference/operator/size/
     */
    public function size(int $size): static
    {
        $this->query->size($size);

        return $this;
    }

    /**
     * Specify $text criteria for the current field.
     *
     * The $language option may be set with {@link Builder::language()}.
     *
     * You can only use this in the first $match stage of a pipeline.
     *
     * @see Expr::text()
     * @see https://docs.mongodb.com/master/reference/operator/query/text/
     */
    public function text(string $search): static
    {
        $this->query->text($search);

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
    public function type($type): static
    {
        $this->query->type($type);

        return $this;
    }
}
