<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use function array_values;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/autocomplete/
 */
class Autocomplete extends AbstractSearchOperator implements ScoredSearchOperator
{
    use ScoredSearchOperatorTrait;

    /** @var list<string> */
    private array $query;
    private string $path;
    private string $tokenOrder = '';
    private ?object $fuzzy     = null;

    public function __construct(Search $search, string $path, string ...$query)
    {
        parent::__construct($search);

        $this->query(...$query);
        $this->path($path);
    }

    public function query(string ...$query): static
    {
        $this->query = array_values($query);

        return $this;
    }

    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    public function tokenOrder(string $order): static
    {
        $this->tokenOrder = $order;

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

    public function getOperatorName(): string
    {
        return 'autocomplete';
    }

    public function getOperatorParams(): object
    {
        $params = (object) [
            'query' => $this->query,
            'path' => $this->path,
        ];

        if ($this->tokenOrder) {
            $params->tokenOrder = $this->tokenOrder;
        }

        if ($this->fuzzy) {
            $params->fuzzy = $this->fuzzy;
        }

        return $this->appendScore($params);
    }
}
