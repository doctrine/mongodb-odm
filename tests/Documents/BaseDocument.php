<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\MappedSuperclass */
abstract class BaseDocument
{
    public $persisted = false;

    /** @ODM\String */
    protected $inheritedProperty;

    public function setInheritedProperty($value)
    {
        $this->inheritedProperty = $value;
    }

    public function getInheritedProperty()
    {
        return $this->inheritedProperty;
    }

    /** @ODM\PrePersist */
    public function prePersist()
    {
        $this->persisted = true;
    }
}