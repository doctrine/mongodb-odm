<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 * @ODM\HasLifecycleCallbacks
 */
class EmbeddedTestLevel2
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /** @var bool */
    public $preRemove = false;

    /** @var bool */
    public $postRemove = false;

    /** @var bool */
    public $preLoad = false;

    /** @var bool */
    public $postLoad = false;

    /** @ODM\PreRemove */
    public function onPreRemove(): void
    {
        $this->preRemove = true;
    }

    /** @ODM\PostRemove */
    public function onPostRemove(): void
    {
        $this->postRemove = true;
    }

    /** @ODM\PreLoad */
    public function onPreLoad(): void
    {
        $this->preLoad = true;
    }

    /** @ODM\PostLoad */
    public function onPostLoad(): void
    {
        $this->postLoad = true;
    }
}
