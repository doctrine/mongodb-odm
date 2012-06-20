<?php

namespace Doctrine\ODM\MongoDB\Tests\Query\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetaData;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;

class Filter extends BsonFilter
{
    public function addFilterCriteria(ClassMetadata $targetMetadata)
    {
        return ($targetMetadata->name == $this->parameters['class'])
            ? array($this->parameters['field'] => $this->parameters['value'])
            : array();
    }
}
