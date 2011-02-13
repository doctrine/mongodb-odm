<?php

namespace Documents;

/** @Document(collection="employees") */
class Employee extends BaseEmployee
{
    /** @ReferenceOne(targetDocument="Documents\Manager") */
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