<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="functional_tests") */
class SimpleEmbedAndReference
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument="Embedded") */
    public $embedMany = array();

    /** @ODM\ReferenceMany(targetDocument="Reference") */
    public $referenceMany = array();

    /** @ODM\EmbedOne(targetDocument="Embedded") */
    public $embedOne;

    /** @ODM\ReferenceOne(targetDocument="Reference") */
    public $referenceOne;
}