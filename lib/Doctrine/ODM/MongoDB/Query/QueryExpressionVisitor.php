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

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

/**
 * Converts Collection expressions to query expressions.
 *
 * @since  1.0
 */
class QueryExpressionVisitor extends ExpressionVisitor
{
    /**
     * Map Criteria API comparison operators to query builder methods
     *
     * @todo Implement support for Comparison::CONTAINS
     * @var array
     */
    private static $operatorMethods = array(
        Comparison::EQ => 'equals',
        Comparison::GT => 'gt',
        Comparison::GTE => 'gte',
        Comparison::IN => 'in',
        Comparison::IS => 'equals',
        Comparison::LT => 'lt',
        Comparison::LTE => 'lte',
        Comparison::NEQ => 'notEqual',
        Comparison::NIN => 'notIn',
    );

    /**
     * Map Criteria API composite types to query builder methods
     *
     * @var array
     */
    private static $compositeMethods = array(
        CompositeExpression::TYPE_AND => 'addAnd',
        CompositeExpression::TYPE_OR => 'addOr',
    );

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * Constructor.
     *
     * @param Builder $builder
     */
    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Converts a comparison expression into the target query language output.
     *
     * @see ExpressionVisitor::walkComparison()
     * @param Comparison $comparison
     * @return \Doctrine\ODM\MongoDB\Query\Expr
     */
    public function walkComparison(Comparison $comparison)
    {
        switch ($comparison->getOperator()) {
            case Comparison::EQ:
            case Comparison::GT:
            case Comparison::GTE:
            case Comparison::IN:
            case Comparison::IS:
            case Comparison::LT:
            case Comparison::LTE:
            case Comparison::NEQ:
            case Comparison::NIN:
                $method = self::$operatorMethods[$comparison->getOperator()];

                return $this->builder->expr()
                    ->field($comparison->getField())
                    ->{$method}($this->walkValue($comparison->getValue()));

            case Comparison::CONTAINS:
                $value = $this->walkValue($comparison->getValue());

                return $this->builder->expr()
                    ->field($comparison->getField())
                    ->equals(new \MongoRegex('/' . preg_quote($value, '/') . '/'));

            default:
                throw new \RuntimeException('Unknown comparison operator: ' . $comparison->getOperator());
        }
    }

    /**
     * Converts a composite expression into the target query language output.
     *
     * @see ExpressionVisitor::walkCompositeExpression()
     * @param CompositeExpression $compositeExpr
     * @return \Doctrine\ODM\MongoDB\Query\Expr
     */
    public function walkCompositeExpression(CompositeExpression $compositeExpr)
    {
        if ( ! isset(self::$compositeMethods[$compositeExpr->getType()])) {
            throw new \RuntimeException('Unknown composite ' . $compositeExpr->getType());
        }

        $method = self::$compositeMethods[$compositeExpr->getType()];
        $expr = $this->builder->expr();

        foreach ($compositeExpr->getExpressionList() as $child) {
            $expr->{$method}($this->dispatch($child));
        }

        return $expr;
    }

    /**
     * Converts a value expression into the target query language part.
     *
     * @see ExpressionVisitor::walkValue()
     * @param Value $value
     * @return mixed
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }
}
