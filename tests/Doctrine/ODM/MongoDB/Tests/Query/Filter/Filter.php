<?php

namespace Doctrine\ODM\MongoDB\Tests\Query\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;

class Filter extends BsonFilter
{
    public function addFilterCriteria(ClassMetadata $class)
    {
        return ($class->name == $this->parameters['class'])
            ? array($this->parameters['field'] => $this->parameters['value'])
            : array();
    }
}
