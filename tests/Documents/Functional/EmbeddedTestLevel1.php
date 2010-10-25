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