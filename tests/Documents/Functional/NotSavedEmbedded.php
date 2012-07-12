<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class NotSavedEmbedded
{
    /** @ODM\String */
    public $name;

    /** @ODM\NotSaved */
    public $notSaved;
}
