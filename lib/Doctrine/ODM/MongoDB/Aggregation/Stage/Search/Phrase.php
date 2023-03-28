<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use function array_values;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/phrase/
 */
class Phrase extends AbstractSearchOperator implements ScoredSearchOperator
{
    use ScoredSearchOperatorTrait;

    /** @var list<string> */
    private array $query = [];

    /** @var list<string> */
    private array $path = [];
    private ?int $slop  = null;

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

    public function slop(int $slop): static
    {
        $this->slop = $slop;

        return $this;
    }

    public function getOperatorName(): string
    {
        return 'phrase';
    }

    public function getOperatorParams(): object
    {
        $params = (object) [
            'query' => $this->query,
            'path' => $this->path,
        ];

        if ($this->slop !== null) {
            $params->slop = $this->slop;
        }

        return $this->appendScore($params);
    }
}
