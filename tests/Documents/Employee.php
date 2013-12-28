<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class Employee extends BaseEmployee
{
    /** @ODM\ReferenceOne(targetDocument="Documents\Manager") */
    private $manager;

    public function getManager()
    {
        return $this->manager;
    }

    public function setManager($val)
    {
        $this->manager = $val;
        return $this;
    }
}