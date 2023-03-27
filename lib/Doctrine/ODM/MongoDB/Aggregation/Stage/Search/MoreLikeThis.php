<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use function array_values;

/** @internal */
class MoreLikeThis extends AbstractSearchOperator
{
    /** @var list<array<string, mixed>|object> */
    private array $like = [];

    /** @param array<string, mixed>|object $documents */
    public function __construct(Search $search, ...$documents)
    {
        parent::__construct($search);

        $this->like = array_values($documents);
    }

    /** @return array<string, object> */
    public function getExpression(): array
    {
        return [$this->getOperatorName() => $this->getOperatorParams()];
    }

    public function getOperatorName(): string
    {
        return 'moreLikeThis';
    }

    public function getOperatorParams(): object
    {
        return (object) ['like' => $this->like];
    }
}
