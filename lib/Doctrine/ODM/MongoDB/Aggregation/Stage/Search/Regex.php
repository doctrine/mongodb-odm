<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/regex/
 */
class Regex extends AbstractSearchOperator implements ScoredSearchOperator
{
    use ScoredSearchOperatorTrait;

    /** @var list<string> */
    private array $query = [];

    /** @var list<string> */
    private array $path               = [];
    private ?bool $allowAnalyzedField = null;

    public function query(string ...$query): static
    {
        $this->query = $query;

        return $this;
    }

    public function path(string ...$path): static
    {
        $this->path = $path;

        return $this;
    }

    public function allowAnalyzedField(bool $allowAnalyzedField = true): static
    {
        $this->allowAnalyzedField = $allowAnalyzedField;

        return $this;
    }

    public function getOperatorName(): string
    {
        return 'regex';
    }

    public function getOperatorParams(): object
    {
        $params = (object) [
            'query' => $this->query,
            'path' => $this->path,
        ];

        if ($this->allowAnalyzedField !== null) {
            $params->allowAnalyzedField = $this->allowAnalyzedField;
        }

        return $this->appendScore($params);
    }
}
