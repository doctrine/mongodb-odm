<?php

declare(strict_types=1);

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class EmbedManyInArrayCollectionLevel1
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, MODM160Level2> */
    #[ODM\EmbedMany(targetDocument: MODM160Level2::class)]
    public $level2;

    public function __construct()
    {
        $this->level2 = new ArrayCollection();
    }
}
