<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use function array_values;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/text/
 */
class Text extends AbstractSearchOperator implements ScoredSearchOperator
{
    use ScoredSearchOperatorTrait;

    /** @var list<string> */
    private array $query = [];

    /** @var list<string> */
    private array $path      = [];
    private ?object $fuzzy   = null;
    private string $synonyms = '';

    public function query(string ...$query): static
    {
        $this->query = array_values($query);

        return $this;
    }

    public function path(string ...$path): static
    {
        $this->path = $path;

        return $this;
    }

    public function fuzzy(?int $maxEdits = null, ?int $prefixLength = null, ?int $maxExpansions = null): static
    {
        $this->fuzzy = (object) [];
        if ($maxEdits !== null) {
            $this->fuzzy->maxEdits = $maxEdits;
        }

        if ($prefixLength !== null) {
            $this->fuzzy->prefixLength = $prefixLength;
        }

        if ($maxExpansions !== null) {
            $this->fuzzy->maxExpansions = $maxExpansions;
        }

        return $this;
    }

    public function synonyms(string $synonyms): static
    {
        $this->synonyms = $synonyms;

        return $this;
    }

    public function getOperatorName(): string
    {
        return 'text';
    }

    public function getOperatorParams(): object
    {
        $params = (object) [
            'query' => $this->query,
            'path' => $this->path,
        ];

        if ($this->fuzzy) {
            $params->fuzzy = $this->fuzzy;
        }

        if ($this->synonyms) {
            $params->synonyms = $this->synonyms;
        }

        return $this->appendScore($params);
    }
}
