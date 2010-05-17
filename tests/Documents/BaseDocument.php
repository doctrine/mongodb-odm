<?php

namespace Documents;

/** @MappedSuperClass */
class BaseDocument
{
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
}