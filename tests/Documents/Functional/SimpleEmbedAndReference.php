<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="functional_tests") */
class SimpleEmbedAndReference
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument="Reference") */
    public $embedMany = [];

    /** @ODM\ReferenceMany(targetDocument="Embedded") */
    public $referenceMany = [];

    /** @ODM\EmbedOne(targetDocument="Reference") */
    public $embedOne;

    /** @ODM\ReferenceOne(targetDocument="Embedded") */
    public $referenceOne;
}
