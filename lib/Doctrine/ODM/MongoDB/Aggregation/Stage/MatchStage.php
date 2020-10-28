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

    /**
     * {@inheritdoc}
     */
    public function __construct(Builder $builder)
    {
        parent::__construct($builder);

        $this->query = $this->expr();
    }

    /**
     * @see http://php.net/manual/en/language.oop5.cloning.php
     */
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
     * @see http://docs.mongodb.org/manual/reference/operator/and/
     *
     * @param array|Expr $expression
     * @param array|Expr ...$expressions
     */
    public function addAnd($expression, ...$expressions) : self
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
     * @see http://docs.mongodb.org/manual/reference/operator/nor/
     *
     * @param array|Expr $expression
     * @param array|Expr ...$expressions
     */
    public function addNor($expression, ...$expressions) : self
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
     * @see http://docs.mongodb.org/manual/reference/operator/or/
     *
     * @param array|Expr $expression
     * @param array|Expr ...$expressions
     */
    public function addOr($expression, ...$expressions) : self
    {
        $this->query->addOr(...func_get_args());

        return $this;
    }

    /**
     * Specify $all criteria for the current field.
     *
     * @see Expr::all()
     * @see http://docs.mongodb.org/manual/reference/operator/all/
     */
    public function all(array $values) : self
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
     * @see http://docs.mongodb.org/manual/reference/operator/elemMatch/
     *
     * @param array|Expr $expression
     */
    public function elemMatch($expression) : self
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
    public function equals($value) : self
    {
        $this->query->equals($value);

        return $this;
    }

    /**
     * Specify $exists criteria for the current field.
     *
     * @see Expr::exists()
     * @see http://docs.mongodb.org/manual/reference/operator/exists/
     */
    public function exists(bool $bool) : self
    {
        $this->query->exists($bool);

        return $this;
    }

    /**
     * Create a new Expr instance that can be used to build partial expressions
     * for other operator methods.
     */
    public function expr() : Expr
    {
        return $this->builder->matchExpr();
    }

    /**
     * Set the current field for building the expression.
     *
     * @see Expr::field()
     */
    public function field(string $field) : self
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
     * @see http://docs.mongodb.org/manual/reference/operator/geoIntersects/
     *
     * @param array|Geometry $geometry
     */
    public function geoIntersects($geometry) : self
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
     * @see http://docs.mongodb.org/manual/reference/operator/geoWithin/
     */
    public function geoWithin(Geometry $geometry) : self
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
     * @see http://docs.mongodb.org/manual/reference/operator/box/
     */
    public function geoWithinBox(float $x1, float $y1, float $x2, float $y2) : self
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
     * @see http://docs.mongodb.org/manual/reference/operator/center/
     */
    public function geoWithinCenter(float $x, float $y, float $radius) : self
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
     * @see http://docs.mongodb.org/manual/reference/operator/centerSphere/
     */
    public function geoWithinCenterSphere(float $x, float $y, float $radius) : self
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
     * @see http://docs.mongodb.org/manual/reference/operator/polygon/
     *
     * @param array $point1    First point of the polygon
     * @param array $point2    Second point of the polygon
     * @param array $point3    Third point of the polygon
     * @param array ...$points Additional points of the polygon
     */
    public function geoWithinPolygon($point1, $point2, $point3, ...$points) : self
    {
        $this->query->geoWithinPolygon(...func_get_args());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression() : array
    {
        return [
            '$match' => $this->query->getQuery() ?: (object) [],
        ];
    }

    /**
     * Specify $gt criteria for the current field.
     *
     * @see Expr::gt()
     * @see http://docs.mongodb.org/manual/reference/operator/gt/
     *
     * @param mixed $value
     */
    public function gt($value) : self
    {
        $this->query->gt($value);

        return $this;
    }

    /**
     * Specify $gte criteria for the current field.
     *
     * @see Expr::gte()
     * @see http://docs.mongodb.org/manual/reference/operator/gte/
     *
     * @param mixed $value
     */
    public function gte($value) : self
    {
        $this->query->gte($value);

        return $this;
    }

    /**
     * Specify $in criteria for the current field.
     *
     * @see Expr::in()
     * @see http://docs.mongodb.org/manual/reference/operator/in/
     */
    public function in(array $values) : self
    {
        $this->query->in($values);

        return $this;
    }

    public function includesReferenceTo(object $document) : self
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
     * @see http://docs.mongodb.org/manual/reference/operator/text/
     */
    public function language(string $language) : self
    {
        $this->query->language($language);

        return $this;
    }

    /**
     * Specify $lt criteria for the current field.
     *
     * @see Expr::lte()
     * @see http://docs.mongodb.org/manual/reference/operator/lte/
     *
     * @param mixed $value
     */
    public function lt($value) : self
    {
        $this->query->lt($value);

        return $this;
    }

    /**
     * Specify $lte criteria for the current field.
     *
     * @see Expr::lte()
     * @see http://docs.mongodb.org/manual/reference/operator/lte/
     *
     * @param mixed $value
     */
    public function lte($value) : self
    {
        $this->query->lte($value);

        return $this;
    }

    /**
     * Specify $mod criteria for the current field.
     *
     * @see Expr::mod()
     * @see http://docs.mongodb.org/manual/reference/operator/mod/
     *
     * @param float|int $divisor
     * @param float|int $remainder
     */
    public function mod($divisor, $remainder = 0) : self
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
     * @see http://docs.mongodb.org/manual/reference/operator/not/
     *
     * @param array|Expr $expression
     */
    public function not($expression) : self
    {
        $this->query->not($expression);

        return $this;
    }

    /**
     * Specify $ne criteria for the current field.
     *
     * @see Expr::notEqual()
     * @see http://docs.mongodb.org/manual/reference/operator/ne/
     *
     * @param mixed $value
     */
    public function notEqual($value) : self
    {
        $this->query->notEqual($value);

        return $this;
    }

    /**
     * Specify $nin criteria for the current field.
     *
     * @see Expr::notIn()
     * @see http://docs.mongodb.org/manual/reference/operator/nin/
     */
    public function notIn(array $values) : self
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
    public function range($start, $end) : self
    {
        $this->query->range($start, $end);

        return $this;
    }

    public function references(object $document) : self
    {
        $this->query->references($document);

        return $this;
    }

    /**
     * Specify $size criteria for the current field.
     *
     * @see Expr::size()
     * @see http://docs.mongodb.org/manual/reference/operator/size/
     */
    public function size(int $size) : self
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
     * @see http://docs.mongodb.org/master/reference/operator/query/text/
     */
    public function text(string $search) : self
    {
        $this->query->text($search);

        return $this;
    }

    /**
     * Specify $type criteria for the current field.
     *
     * @see Expr::type()
     * @see http://docs.mongodb.org/manual/reference/operator/type/
     *
     * @param int|string $type
     */
    public function type($type) : self
    {
        $this->query->type($type);

        return $this;
    }
}
