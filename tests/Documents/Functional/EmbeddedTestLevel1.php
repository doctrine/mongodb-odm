<?php

namespace Documents\Functional;

/**
 * @EmbeddedDocument
 * @HasLifecycleCallbacks
 */
class EmbeddedTestLevel1
{
    /** @String */
    public $name;
    /** @EmbedMany(targetDocument="EmbeddedTestLevel2") */
    public $level2 = array();

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
