<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $count stage to an aggregation pipeline.
 *
 * @psalm-type CountStageExpression = array{'$count': string}
 */
class Count extends Stage
{
    private string $fieldName;

    public function __construct(Builder $builder, string $fieldName)
    {
        parent::__construct($builder);

        $this->fieldName = $fieldName;
    }

    /** @psalm-return CountStageExpression */
    public function getExpression(): array
    {
        return [
            '$count' => $this->fieldName,
        ];
    }
}
