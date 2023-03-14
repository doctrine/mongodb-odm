<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

/**
 * Fluent interface for adding a $addFields stage to an aggregation pipeline.
 */
class AddFields extends Operator
{
    private bool $isSet = false;

    protected function isSet(): void
    {
        $this->isSet = true;
    }

    public function getExpression(): array
    {
        $name = $this->isSet ? '$set' : '$addFields';

        return [
            $name => $this->expr->getExpression(),
        ];
    }
}
