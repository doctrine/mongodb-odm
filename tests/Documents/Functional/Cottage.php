<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\Document
 */
class Cottage extends House
{
    /** @ODM\Boolean */
    protected $hasChimney;

    public function setHasChimney($hasChimney)
    {
        $this->hasChimney = $hasChimney;
    }

    public function getHasChimney()
    {
        return $this->hasChimney;
    }

}

