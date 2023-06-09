<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Aggregation\Stage;

use Doctrine\ODM\MongoDB\Aggregation\Builder;
use Doctrine\ODM\MongoDB\Aggregation\Stage;

class Documents extends Stage
{
    /**
     * @var array<mixed>
     */
    private array $documents = [];

    /**
     * @param array<mixed> $documents
     */
    public function __construct(Builder $builder, array $documents = [])
    {
        parent::__construct($builder);
        $this->documents = $documents;
    }

    public function getExpression(): array
    {
        return ['$documents' => $this->documents];
    }
}
