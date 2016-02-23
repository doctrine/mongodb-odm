<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument @ODM\HasLifecycleCallbacks */
class EmbeddedTestLevel1
{
    /** @ODM\Field(type="string") */
    public $name;
    /** @ODM\EmbedMany(targetDocument="EmbeddedTestLevel2") */
    public $level2 = array();

    public $preRemove = false;
    public $postRemove = false;
    public $preLoad = false;
    public $postLoad = false;

    /** @ODM\PreRemove */
    public function onPreRemove()
    {
        $this->preRemove = true;
    }

    /** @ODM\PostRemove */
    public function onPostRemove()
    {
        $this->postRemove = true;
    }

    /** @ODM\PreLoad */
    public function onPreLoad()
    {
        $this->preLoad = true;
    }

    /** @ODM\PostLoad */
    public function onPostLoad()
    {
        $this->postLoad = true;
    }
}
