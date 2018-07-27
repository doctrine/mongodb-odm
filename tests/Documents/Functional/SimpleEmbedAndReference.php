<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="functional_tests") */
class SimpleEmbedAndReference
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument=Embedded::class) */
    public $embedMany = [];

    /** @ODM\ReferenceMany(targetDocument=Reference::class) */
    public $referenceMany = [];

    /** @ODM\EmbedOne(targetDocument=Embedded::class) */
    public $embedOne;

    /** @ODM\ReferenceOne(targetDocument=Reference::class) */
    public $referenceOne;
}
