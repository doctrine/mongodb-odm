<?php

declare(strict_types=1);

namespace Documents\Functional\Ticket\GH683;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 * @ODM\DiscriminatorField("type")
 * @ODM\DiscriminatorMap({"e1"=EmbeddedSubDocument1::class, "e2"=EmbeddedSubDocument2::class})
 */
class AbstractEmbedded
{
    /** @ODM\Field(type="string") */
    public $name;
}
