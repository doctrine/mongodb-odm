<?php

namespace Doctrine\ODM\MongoDB\Tests\Query\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetaData;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Documents\User;

class Filter extends BsonFilter
{
    public function addFilterCriteria(ClassMetadata $targetMetadata)
    {
        if ($targetMetadata->name == $this->parameters['class']){
            return array($this->parameters['field'] => $this->parameters['value']);
        }
        return array();
    }

}
