<?php

namespace Doctrine\ODM\MongoDB\Tests\Tools\GH297;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class User
{
    use AddressTrait;
    
    /** @ODM\Id */
    private $id;
    
    /** @ODM\Field(type="string") */
    private $name;
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function setName($name)
    {
        $this->name = $name;
    }
}
