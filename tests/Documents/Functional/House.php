<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class House extends Building
{
    /** @ODM\String */
    protected $ownerName;

    public function setOwnerName($ownerName)
    {
        $this->ownerName = $ownerName;
    }

    public function getOwnerName()
    {
        return $this->ownerName;
    }

}

