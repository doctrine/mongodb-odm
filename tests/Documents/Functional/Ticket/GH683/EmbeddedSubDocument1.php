<?php

namespace Documents\Functional\Ticket\GH683;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @ODM\EmbeddedDocument
 */
class EmbeddedSubDocument1 extends AbstractEmbedded
{
    /** @ODM\Field(type="string") */
    public $name;
}
