<?php

namespace Doctrine\ODM\MongoDB\Tests\Tools\GH297;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class Address
{
    /** @ODM\Field(type="string") */
    private $street;
    
    public function getStreet()
    {
        return $this->street;
    }
    
    public function setStreet($street)
    {
        $this->street = $street;
    }
}
