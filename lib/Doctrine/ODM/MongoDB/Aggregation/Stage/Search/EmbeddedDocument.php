<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/embeddedDocument/
 */
class EmbeddedDocument extends AbstractSearchOperator implements SupportsEmbeddableSearchOperators, ScoredSearchOperator
{
    use SupportsAllSearchOperatorsTrait;
    use ScoredSearchOperatorTrait;

    private string $path;
    private ?SearchOperator $operator = null;

    public function __construct(Search $search, string $path)
    {
        parent::__construct($search);

        $this->path($path);
    }

    public function path(string $path): static
    {
        $this->path = $path;

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

    public function getOperatorName(): string
    {
        return 'embeddedDocument';
    }

    public function getOperatorParams(): object
    {
        $params = (object) ['path' => $this->path];

        if ($this->operator) {
            $params->operator = (object) $this->operator->getExpression();
        }

        return $this->appendScore($params);
    }
}
