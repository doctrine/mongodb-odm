<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/exists/
 */
class Exists extends AbstractSearchOperator
{
    public function __construct(Search $search, private string $path = '')
    {
        parent::__construct($search);
    }

    public function getOperatorName(): string
    {
        return 'exists';
    }

    public function getOperatorParams(): object
    {
        return (object) ['path' => $this->path];
    }
}
