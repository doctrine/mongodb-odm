<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Expr;
use Doctrine\ODM\MongoDB\Aggregation\Operator\GroupAccumulatorOperators;
use Doctrine\ODM\MongoDB\Aggregation\Operator\ProvidesGroupAccumulatorOperators;

/**
 * Fluent interface for adding a $group stage to an aggregation pipeline.
 *
 * @phpstan-type GroupStageExpression array{'$group': array<string, mixed>}
 */
class Group extends Operator implements GroupAccumulatorOperators
{
    use ProvidesGroupAccumulatorOperators;

    /** @var Expr */
    protected $expr;

    public function __construct(Builder $builder)
    {
        parent::__construct($builder);

        $this->expr = $builder->expr();
    }

    /** @phpstan-return GroupStageExpression */
    public function getExpression(): array
    {
        return [
            '$group' => $this->expr->getExpression(),
        ];
    }

    protected function getExpr(): Expr
    {
        return $this->expr;
    }
}
