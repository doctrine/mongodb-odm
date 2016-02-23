<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\MappedSuperclass @ODM\HasLifecycleCallbacks */
abstract class BaseDocument
{
    public $persisted = false;

    /** @ODM\Field(type="string") */
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
