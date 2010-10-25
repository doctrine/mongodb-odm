<?php

namespace Documents\Functional;

/**
 * @EmbeddedDocument
 * @HasLifecycleCallbacks
 */
class EmbeddedTestLevel2
{
    /** @String */
    public $name;

    public $preRemove = false;
    public $postRemove = false;
    public $preLoad = false;
    public $postLoad = false;

    /** @PreRemove */
    public function onPreRemove()
    {
        $this->preRemove = true;
    }

    /** @PostRemove */
    public function onPostRemove()
    {
        $this->postRemove = true;
    }

    /** @PreLoad */
    public function onPreLoad()
    {
        $this->preLoad = true;
    }

    /** @PostLoad */
    public function onPostLoad()
    {
        $this->postLoad = true;
    }
}