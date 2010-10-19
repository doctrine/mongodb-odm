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
}
