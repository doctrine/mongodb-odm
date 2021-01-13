<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use MongoDB\BSON\Regex;
use RuntimeException;

/**
 * Converts Collection expressions to query expressions.
 *
 * @internal
 */
final class QueryExpressionVisitor extends ExpressionVisitor
{
    /**
     * Map Criteria API comparison operators to query builder methods
     *
     * @todo Implement support for Comparison::CONTAINS
     * @var array
     */
    private static $operatorMethods = [
        Comparison::EQ => 'equals',
        Comparison::GT => 'gt',
        Comparison::GTE => 'gte',
        Comparison::IN => 'in',
        Comparison::LT => 'lt',
        Comparison::LTE => 'lte',
        Comparison::NEQ => 'notEqual',
        Comparison::NIN => 'notIn',
    ];

    /**
     * Map Criteria API composite types to query builder methods
     *
     * @var array
     */
    private static $compositeMethods = [
        CompositeExpression::TYPE_AND => 'addAnd',
        CompositeExpression::TYPE_OR => 'addOr',
    ];

    /** @var Builder */
    protected $builder;

    public function __construct(Builder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Converts a comparison expression into the target query language output.
     *
     * @see ExpressionVisitor::walkComparison()
     */
    public function walkComparison(Comparison $comparison): Expr
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
                    ->equals(new Regex($value, ''));

            default:
                throw new RuntimeException('Unknown comparison operator: ' . $comparison->getOperator());
        }
    }

    /**
     * Converts a composite expression into the target query language output.
     *
     * @see ExpressionVisitor::walkCompositeExpression()
     */
    public function walkCompositeExpression(CompositeExpression $compositeExpr): Expr
    {
        if (! isset(self::$compositeMethods[$compositeExpr->getType()])) {
            throw new RuntimeException('Unknown composite ' . $compositeExpr->getType());
        }

        $method = self::$compositeMethods[$compositeExpr->getType()];
        $expr   = $this->builder->expr();

        foreach ($compositeExpr->getExpressionList() as $child) {
            $expr->{$method}($this->dispatch($child));
        }

        return $expr;
    }

    /**
     * Converts a value expression into the target query language part.
     *
     * @see ExpressionVisitor::walkValue()
     *
     * @return mixed
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }
}
