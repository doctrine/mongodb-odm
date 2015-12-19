<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="functional_tests") */
class NotSaved
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\NotSaved */
    public $notSaved;

    /** @ODM\EmbedOne(targetDocument="NotSavedEmbedded") */
    public $embedded;
}
