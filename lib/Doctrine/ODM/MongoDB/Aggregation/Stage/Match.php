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
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Query\Expr;
use GeoJson\Geometry\Geometry;

/**
 * Fluent interface for building aggregation pipelines.
 */
class Match extends Stage
{
    /**
     * @var Expr
     */
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
     * @param array|Expr $expression
     * @return $this
     */
    public function addAnd($expression /* , $expression2, ... */)
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
     * @param array|Expr $expression
     * @return $this
     */
    public function addNor($expression /*, $expression2, ... */)
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
     * @param array|Expr $expression
     * @return $this
     */
    public function addOr($expression /* , $expression2, ... */)
    {
        $this->query->addOr(...func_get_args());

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
        $this->query->all($values);

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
     * Specify $elemMatch criteria for the current field.
     *
     * You can create a new expression using the {@link Builder::matchExpr()}
     * method.
     *
     * @see Expr::elemMatch()
     * @see http://docs.mongodb.org/manual/reference/operator/elemMatch/
     * @param array|Expr $expression
     * @return $this
     */
    public function elemMatch($expression)
    {
        $this->query->elemMatch($expression);

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
        $this->query->equals($value);

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
        $this->query->exists((boolean) $bool);

        return $this;
    }

    /**
     * Create a new Expr instance that can be used to build partial expressions
     * for other operator methods.
     *
     * @return Expr $expr
     */
    public function expr()
    {
        return $this->builder->matchExpr();
    }

    /**
     * Set the current field for building the expression.
     *
     * @see Expr::field()
     * @param string $field
     * @return $this
     */
    public function field($field)
    {
        $this->query->field((string) $field);

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
     * @param array|Geometry $geometry
     * @return $this
     */
    public function geoWithin(Geometry $geometry)
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
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @return $this
     */
    public function geoWithinBox($x1, $y1, $x2, $y2)
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
     * @param float $x
     * @param float $y
     * @param float $radius
     * @return $this
     */
    public function geoWithinCenter($x, $y, $radius)
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
     * @param float $x
     * @param float $y
     * @param float $radius
     * @return $this
     */
    public function geoWithinCenterSphere($x, $y, $radius)
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
     * @param array $point,... Three or more point coordinate tuples
     * @return $this
     */
    public function geoWithinPolygon(/* array($x1, $y1), ... */)
    {
        $this->query->geoWithinPolygon(...func_get_args());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getExpression()
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
     * @param mixed $value
     * @return $this
     */
    public function gt($value)
    {
        $this->query->gt($value);

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
        $this->query->gte($value);

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
        $this->query->in($values);

        return $this;
    }

    /**
     * @param object $document
     * @return $this
     */
    public function includesReferenceTo($document)
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
     * @param string $language
     * @return $this
     */
    public function language($language)
    {
        $this->query->language($language);

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
        $this->query->lt($value);

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
        $this->query->lte($value);

        return $this;
    }

    /**
     * Add $maxDistance criteria to the query.
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
        $this->query->maxDistance($maxDistance);

        return $this;
    }

    /**
     * Add $minDistance criteria to the query.
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
        $this->query->minDistance($minDistance);

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
     * @param array|Expr $expression
     * @return $this
     */
    public function not($expression)
    {
        $this->query->not($expression);

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
        $this->query->notEqual($value);

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
     * @param mixed $start
     * @param mixed $end
     * @return $this
     */
    public function range($start, $end)
    {
        $this->query->range($start, $end);

        return $this;
    }

    /**
     * @param object $document
     * @return $this
     */
    public function references($document)
    {
        $this->query->references($document);

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
        $this->query->size((integer) $size);

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
     * @param string $search
     * @return $this
     */
    public function text($search)
    {
        $this->query->text($search);

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
        $this->query->type($type);

        return $this;
    }
}
