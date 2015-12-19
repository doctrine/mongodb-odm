<?php

namespace Documents\Functional\Ticket\GH683;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="gh683_test") */
class ParentDocument
{
    /** @ODM\Id */
    public $id;
    /** @ODM\Field(type="string") */
    public $name;
    /** @ODM\EmbedOne(targetDocument="AbstractEmbedded") */
    public $embedOne;
    /** @ODM\EmbedMany(targetDocument="AbstractEmbedded") */
    public $embedMany;
}
