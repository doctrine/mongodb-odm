<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="functional_tests") */
class SimpleEmbedAndReference
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument="Embedded") */
    public $embedMany = [];

    /** @ODM\ReferenceMany(targetDocument="Reference") */
    public $referenceMany = [];

    /** @ODM\EmbedOne(targetDocument="Embedded") */
    public $embedOne;

    /** @ODM\ReferenceOne(targetDocument="Reference") */
    public $referenceOne;
}
