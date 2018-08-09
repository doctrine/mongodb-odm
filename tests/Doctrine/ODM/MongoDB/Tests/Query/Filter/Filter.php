<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Query\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;

class Filter extends BsonFilter
{
    public function addFilterCriteria(ClassMetadata $class): array
    {
        return ($class->name === $this->parameters['class'])
            ? [$this->parameters['field'] => $this->parameters['value']]
            : [];
    }
}
