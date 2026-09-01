<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use MongoDB\BSON\UTCDateTime;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/range/
 */
class Range extends AbstractSearchOperator implements ScoredSearchOperator
{
    use ScoredSearchOperatorTrait;

    private int|float|UTCDateTime|null $gt = null;
    private int|float|UTCDateTime|null $lt = null;
    private bool $includeLowerBound        = false;

    /** @var list<string> */
    private array $path;

    public function gt(int|float|UTCDateTime|null $value): static
    {
        $this->gt                = $value;
        $this->includeLowerBound = false;

        return $this;
    }

    public function gte(int|float|UTCDateTime|null $value): static
    {
        $this->gt                = $value;
        $this->includeLowerBound = true;

        return $this;
    }

    public function lt(int|float|UTCDateTime|null $value): static
    {
        $this->lt                = $value;
        $this->includeLowerBound = false;

        return $this;
    }

    public function lte(int|float|UTCDateTime|null $value): static
    {
        $this->lt                = $value;
        $this->includeLowerBound = true;

        return $this;
    }

    public function path(string ...$path): static
    {
        $this->path = $path;

        return $this;
    }

    public function getOperatorName(): string
    {
        return 'range';
    }

    public function getOperatorParams(): object
    {
        $params = (object) ['path' => $this->prepareFieldPath($this->path)];

        if ($this->gt !== null) {
            $name          = $this->includeLowerBound ? 'gte' : 'gt';
            $params->$name = $this->gt;
        }

        if ($this->lt !== null) {
            $name          = $this->includeLowerBound ? 'lte' : 'lt';
            $params->$name = $this->lt;
        }

        return $this->appendScore($params);
    }
}
