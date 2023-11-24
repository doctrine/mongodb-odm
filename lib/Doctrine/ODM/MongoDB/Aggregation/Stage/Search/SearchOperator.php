<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

/** @internal */
interface SearchOperator
{
    /**
     * @internal
     *
     * @return array<string, object>
     */
    public function getExpression(): array;

    /** @internal */
    public function getOperatorName(): string;

    /** @internal */
    public function getOperatorParams(): object;
}
