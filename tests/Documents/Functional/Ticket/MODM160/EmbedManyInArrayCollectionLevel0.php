<?php

declare(strict_types=1);

namespace Documents\Functional\Ticket\MODM160;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'embedded_test')]
class EmbedManyInArrayCollectionLevel0
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, EmbedManyInArrayCollectionLevel1> */
    #[ODM\EmbedMany(targetDocument: EmbedManyInArrayCollectionLevel1::class)]
    public $level1;

    public function __construct()
    {
        $this->level1 = new ArrayCollection();
    }
}
