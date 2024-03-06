<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
#[ODM\HasLifecycleCallbacks]
class EmbeddedTestLevel1
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, EmbeddedTestLevel2>|array<EmbeddedTestLevel2> */
    #[ODM\EmbedMany(targetDocument: EmbeddedTestLevel2::class)]
    public $level2 = [];

    /** @var bool */
    public $preRemove = false;

    /** @var bool */
    public $postRemove = false;

    /** @var bool */
    public $preLoad = false;

    /** @var bool */
    public $postLoad = false;

    #[ODM\PreRemove]
    public function onPreRemove(): void
    {
        $this->preRemove = true;
    }

    #[ODM\PostRemove]
    public function onPostRemove(): void
    {
        $this->postRemove = true;
    }

    #[ODM\PreLoad]
    public function onPreLoad(): void
    {
        $this->preLoad = true;
    }

    #[ODM\PostLoad]
    public function onPostLoad(): void
    {
        $this->postLoad = true;
    }
}
