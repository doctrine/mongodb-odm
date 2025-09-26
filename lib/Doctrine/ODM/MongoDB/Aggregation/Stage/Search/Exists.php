<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage\Search;

use Doctrine\ODM\MongoDB\Aggregation\Stage\Search;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;

/**
 * @internal
 *
 * @see https://www.mongodb.com/docs/atlas/atlas-search/exists/
 */
class Exists extends AbstractSearchOperator
{
    public function __construct(Search $search, DocumentPersister $persister, private string $path = '')
    {
        parent::__construct($search, $persister);
    }

    public function getOperatorName(): string
    {
        return 'exists';
    }

    public function getOperatorParams(): object
    {
        return (object) ['path' => $this->prepareFieldPath($this->path)];
    }
}
