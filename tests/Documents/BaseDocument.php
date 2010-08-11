<?php

namespace Documents;

/**
 * @MappedSuperclass
 * @HasLifecycleCallbacks
 */
abstract class BaseDocument
{
    public $persisted = false;

    /** @String */
    protected $inheritedProperty;

    public function setInheritedProperty($value)
    {
        $this->inheritedProperty = $value;
    }

    public function getInheritedProperty()
    {
        return $this->inheritedProperty;
    }

    /** @PrePersist */
    public function prePersist()
    {
        $this->persisted = true;
    }
}