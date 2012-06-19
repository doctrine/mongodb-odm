<?php

namespace Doctrine\ODM\MongoDB\Tests\Query\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetaData;
use Doctrine\ODM\MongoDB\Query\Filter\BSONFilter;

class Filter extends BSONFilter
{
    public function addFilterCriteria(ClassMetadata $targetMetadata)
    {
        if ($targetMetadata->name == $this->parameters['class']){
            return array($this->parameters['field'] => $this->parameters['value']);
        }
        return array();
    }
}
