<?php

declare(strict_types=1);

namespace Documents\Functional\Ticket\GH683;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class EmbeddedSubDocument2 extends AbstractEmbedded
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
}
