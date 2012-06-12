<?php

namespace Doctrine\ODM\MongoDB\Tests\Query\Filter;

use Doctrine\ODM\MongoDB\Mapping\ClassMetaData;
use Doctrine\ODM\MongoDB\Query\Filter\BsonFilter;
use Documents\User;

class Filter extends BsonFilter
{         
    public function addFilterCriteria(ClassMetadata $targetMetadata)
    {
        $targetClass = $targetMetadata->name;
        if($targetClass == 'Documents\User'){            
            return array('username' => 'Tim');
        } 
        return array(); 
    }

}
