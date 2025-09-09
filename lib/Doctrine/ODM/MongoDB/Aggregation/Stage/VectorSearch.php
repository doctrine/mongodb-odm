<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Query\Expr;
use MongoDB\BSON\Binary;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\Int64;

/**
 * @phpstan-type Vector list<int|Int64>|list<float|Decimal128>|list<bool|0|1>|Binary
 * @phpstan-type VectorSearchStageExpression array{
 *     '$vectorSearch': object{
 *         exact?: bool,
 *         filter?: object,
 *         index?: string,
 *         limit?: int,
 *         numCandidates?: int,
 *         path?: string,
 *         queryVector?: Vector,
 *     }
 * }
 */
class VectorSearch extends Stage
{
    private ?bool $exact        = null;
    private ?Expr $filter       = null;
    private ?string $index      = null;
    private ?int $limit         = null;
    private ?int $numCandidates = null;
    private ?string $path       = null;
    /** @phpstan-var Vector|null */
    private array|Binary|null $queryVector = null;

    public function __construct(Builder $builder)
    {
        parent::__construct($builder);
    }

    public function getExpression(): array
    {
        $params = [];

        if ($this->exact !== null) {
            $params['exact'] = $this->exact;
        }

        if ($this->filter !== null) {
            $params['filter'] = $this->filter->getQuery();
        }

        if ($this->index !== null) {
            $params['index'] = $this->index;
        }

        if ($this->limit !== null) {
            $params['limit'] = $this->limit;
        }

        if ($this->numCandidates !== null) {
            $params['numCandidates'] = $this->numCandidates;
        }

        if ($this->path !== null) {
            $params['path'] = $this->path;
        }

        if ($this->queryVector !== null) {
            $params['queryVector'] = $this->queryVector;
        }

        return [$this->getStageName() => $params];
    }

    public function exact(bool $exact): static
    {
        $this->exact = $exact;

        return $this;
    }

    public function filter(Expr $filter): static
    {
        $this->filter = $filter;

        return $this;
    }

    public function index(string $index): static
    {
        $this->index = $index;

        return $this;
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function numCandidates(int $numCandidates): static
    {
        $this->numCandidates = $numCandidates;

        return $this;
    }

    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /** @phpstan-param Vector $queryVector */
    public function queryVector(array|Binary $queryVector): static
    {
        $this->queryVector = $queryVector;

        return $this;
    }

    protected function getStageName(): string
    {
        return '$vectorSearch';
    }
}
