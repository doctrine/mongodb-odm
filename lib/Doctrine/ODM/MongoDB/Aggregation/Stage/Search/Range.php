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
    private bool $includeUpperBound        = false;

    /** @var list<string> */
    private array $path;

    /** @param int|float|UTCDateTime|null $value */
    public function gt($value): static
    {
        $this->gt                = $value;
        $this->includeLowerBound = false;

        return $this;
    }

    /** @param int|float|UTCDateTime|null $value */
    public function gte($value): static
    {
        $this->gt                = $value;
        $this->includeLowerBound = true;

        return $this;
    }

    /** @param int|float|UTCDateTime|null $value */
    public function lt($value): static
    {
        $this->lt                = $value;
        $this->includeLowerBound = false;

        return $this;
    }

    /** @param int|float|UTCDateTime|null $value */
    public function lte($value): static
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
        $params = (object) ['path' => $this->path];

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
