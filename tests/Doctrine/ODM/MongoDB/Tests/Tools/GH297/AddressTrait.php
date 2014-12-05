<?php

namespace Doctrine\ODM\MongoDB\Tests\Tools\GH297;

trait AddressTrait 
{
    /**
     * @ODM\EmbedOne
     */
    private $address;

    public function getAddress() 
    {
        return $this->address;
    }

    public function setAddress(Address $address) 
    {
        $this->address = $address;
    }
}
