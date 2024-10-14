<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Sort;

/**
 * @internal
 *
 * @phpstan-import-type SortDirectionKeywords from Sort
 * @phpstan-import-type SortMetaKeywords from Search
 * @phpstan-import-type SortMeta from Search
 * @phpstan-import-type SortShape from Search
 */
abstract class AbstractSearchOperator extends Stage implements SearchOperator
{
    public function __construct(private Search $search)
    {
        parent::__construct($search->builder);
    }

    public function index(string $name): Search
    {
        return $this->search->index($name);
    }

    public function countDocuments(string $type, ?int $threshold = null): Search
    {
        return $this->search->countDocuments($type, $threshold);
    }

    public function highlight(string $path, ?int $maxCharsToExamine = null, ?int $maxNumPassages = null): Search
    {
        return $this->search->highlight($path, $maxCharsToExamine, $maxNumPassages);
    }

    public function returnStoredSource(bool $returnStoredSource): Search
    {
        return $this->search->returnStoredSource($returnStoredSource);
    }

    /**
     * @param array<string, int|string>|string $fieldName Field name or array of field/order pairs
     * @param int|string                       $order     Field order (if one field is specified)
     * @phpstan-param SortShape|string $fieldName
     * @phpstan-param int|SortMeta|SortDirectionKeywords|null $order
     */
    public function sort($fieldName, $order = null): Search
    {
        return $this->search->sort($fieldName, $order);
    }

    /** @return non-empty-array<non-empty-string, object> */
    final public function getExpression(): array
    {
        return [$this->getOperatorName() => $this->getOperatorParams()];
    }

    protected function getSearchStage(): Search
    {
        return $this->search;
    }
}
