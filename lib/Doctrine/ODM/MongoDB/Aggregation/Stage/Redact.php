<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $redact stage to an aggregation pipeline.
 *
 */
class Redact extends Operator
{
    /**
     * {@inheritdoc}
     */
    public function getExpression()
    {
        return [
            '$redact' => $this->expr->getExpression(),
        ];
    }
}
