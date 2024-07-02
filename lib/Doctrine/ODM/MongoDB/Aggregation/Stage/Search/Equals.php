<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/equals/
 */
class Equals extends AbstractSearchOperator implements ScoredSearchOperator
{
    use ScoredSearchOperatorTrait;

    private string $path = '';

    private mixed $value;

    /** @param string|int|float|ObjectId|UTCDateTime|null $value */
    public function __construct(Search $search, string $path = '', $value = null)
    {
        parent::__construct($search);

        $this
            ->path($path)
            ->value($value);
    }

    public function path(string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /** @param string|int|float|ObjectId|UTCDateTime|null $value */
    public function value($value): static
    {
        $this->value = $value;

        return $this;
    }

    public function getOperatorName(): string
    {
        return 'equals';
    }

    public function getOperatorParams(): object
    {
        $params = (object) [
            'path' => $this->path,
            'value' => $this->value,
        ];

        return $this->appendScore($params);
    }
}
