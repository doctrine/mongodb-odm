<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

/** @internal */
class Exists extends AbstractSearchOperator
{
    public function __construct(Search $search, private string $path = '')
    {
        parent::__construct($search);
    }

    /** @return array<string, object> */
    public function getExpression(): array
    {
        return [$this->getOperatorName() => $this->getOperatorParams()];
    }

    public function getOperatorName(): string
    {
        return 'exists';
    }

    public function getOperatorParams(): object
    {
        return (object) ['path' => $this->path];
    }
}
