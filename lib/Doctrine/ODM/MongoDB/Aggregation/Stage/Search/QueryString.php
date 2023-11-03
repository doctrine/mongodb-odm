<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/queryString/
 */
class QueryString extends AbstractSearchOperator implements ScoredSearchOperator
{
    use ScoredSearchOperatorTrait;

    private string $query;
    private string $defaultPath;

    public function __construct(Search $search, string $query = '', string $defaultPath = '')
    {
        parent::__construct($search);

        $this
            ->query($query)
            ->defaultPath($defaultPath);
    }

    public function query(string $query): static
    {
        $this->query = $query;

        return $this;
    }

    public function defaultPath(string $defaultPath): static
    {
        $this->defaultPath = $defaultPath;

        return $this;
    }

    public function getOperatorName(): string
    {
        return 'queryString';
    }

    public function getOperatorParams(): object
    {
        $params = (object) [
            'query' => $this->query,
            'defaultPath' => $this->defaultPath,
        ];

        return $this->appendScore($params);
    }
}
