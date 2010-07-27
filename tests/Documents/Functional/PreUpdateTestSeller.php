<?php

namespace Documents\Functional;

/**
 * @Document(collection="pre_update_test_seller")
 * @HasLifecycleCallbacks
 */
class PreUpdateTestSeller
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    /** @PreUpdate */
    public function preUpdate()
    {
    }
}