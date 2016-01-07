<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="pre_update_test_seller") */
class PreUpdateTestSeller
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    /** @ODM\PreUpdate */
    public function preUpdate()
    {
    }
}
