<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Bucket;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Operator\GroupAccumulatorOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ProvidesGroupAccumulatorOperators;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Abstract class with common functionality for output objects in bucket stages
 *
 * @internal
 */
abstract class AbstractOutput extends Stage implements GroupAccumulatorOperators
{
    use ProvidesGroupAccumulatorOperators;

    /** @var Stage\AbstractBucket */
    protected $bucket;

    private Expr $expr;

    public function __construct(Builder $builder, Stage\AbstractBucket $bucket)
    {
        parent::__construct($builder);

        $this->bucket = $bucket;
        $this->expr   = $builder->expr();
    }

    public function getExpression(): array
    {
        return $this->expr->getExpression();
    }

    /**
     * Used to use an expression as field value. Can be any expression.
     *
     * @see https://docs.mongodb.com/manual/meta/aggregation-quick-reference/#aggregation-expressions
     * @see Expr::expression
     *
     * @param mixed|Expr $value
     *
     * @return $this
     */
    public function expression($value): static
    {
        $this->expr->expression($value);

        return $this;
    }

    /**
     * Set the current field for building the expression.
     *
     * @see Expr::field
     *
     * @param string $fieldName
     *
     * @return $this
     */
    public function field($fieldName): static
    {
        $this->expr->field($fieldName);

        return $this;
    }

    protected function getExpr(): Expr
    {
        return $this->expr;
    }
}
