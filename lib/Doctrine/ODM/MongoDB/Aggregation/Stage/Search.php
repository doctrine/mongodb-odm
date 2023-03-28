<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\SearchOperator;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\SupportsAllSearchOperators;
use Doctrine\ODM\MongoDB\Aggregation\Stage\Search\SupportsAllSearchOperatorsTrait;

/**
 * @psalm-type CountType = 'lowerBound'|'total'
 * @psalm-type SearchStageExpression = array{
 *     '$search': object{
 *         index?: string,
 *         count?: object{
 *            type: CountType,
 *            threshold?: int,
 *         },
 *         highlight?: object{
 *             path: string,
 *             maxCharsToExamine?: int,
 *             maxNumPassages?: int,
 *         },
 *         returnStoredSource?: bool,
 *         autocomplete?: object,
 *         compound?: object,
 *         embeddedDocument?: object,
 *         equals?: object,
 *         exists?: object,
 *         geoShape?: object,
 *         geoWithin?: object,
 *         moreLikeThis?: object,
 *         near?: object,
 *         phrase?: object,
 *         queryString?: object,
 *         range?: object,
 *         regex?: object,
 *         text?: object,
 *         wildcard?: object,
 *     }
 * }
 */
class Search extends Stage implements SupportsAllSearchOperators
{
    use SupportsAllSearchOperatorsTrait;

    private string $indexName         = '';
    private ?object $count            = null;
    private ?object $highlight        = null;
    private ?bool $returnStoredSource = null;
    private ?SearchOperator $operator = null;

    public function __construct(Builder $builder)
    {
        parent::__construct($builder);
    }

    /** @psalm-return SearchStageExpression */
    public function getExpression(): array
    {
        $params = (object) [];

        if ($this->indexName) {
            $params->index = $this->indexName;
        }

        if ($this->count) {
            $params->count = $this->count;
        }

        if ($this->highlight) {
            $params->highlight = $this->highlight;
        }

        if ($this->returnStoredSource !== null) {
            $params->returnStoredSource = $this->returnStoredSource;
        }

        if ($this->operator !== null) {
            $operatorName          = $this->operator->getOperatorName();
            $params->$operatorName = $this->operator->getOperatorParams();
        }

        return ['$search' => $params];
    }

    public function index(string $name): static
    {
        $this->indexName = $name;

        return $this;
    }

    /** @psalm-param CountType $type */
    public function countDocuments(string $type, ?int $threshold = null): static
    {
        $this->count = (object) ['type' => $type];

        if ($threshold !== null) {
            $this->count->threshold = $threshold;
        }

        return $this;
    }

    public function highlight(string $path, ?int $maxCharsToExamine = null, ?int $maxNumPassages = null): static
    {
        $this->highlight = (object) ['path' => $path];

        if ($maxCharsToExamine !== null) {
            $this->highlight->maxCharsToExamine = $maxCharsToExamine;
        }

        if ($maxNumPassages !== null) {
            $this->highlight->maxNumPassages = $maxNumPassages;
        }

        return $this;
    }

    public function returnStoredSource(bool $returnStoredSource = true): static
    {
        $this->returnStoredSource = $returnStoredSource;

        return $this;
    }

    /**
     * @param T $operator
     *
     * @return T
     *
     * @template T of SearchOperator
     */
    protected function addOperator(SearchOperator $operator): SearchOperator
    {
        return $this->operator = $operator;
    }

    protected function getSearchStage(): static
    {
        return $this;
    }
}
