<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document(collection="functional_tests") */
class SimpleEmbedAndReference
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedMany(targetDocument=Embedded::class)
     *
     * @var Collection<int, Embedded>|array<Embedded>
     */
    public $embedMany = [];

    /**
     * @ODM\ReferenceMany(targetDocument=Reference::class)
     *
     * @var Collection<int, Reference>|array<Reference>
     */
    public $referenceMany = [];

    /**
     * @ODM\EmbedOne(targetDocument=Embedded::class)
     *
     * @var Embedded|null
     */
    public $embedOne;

    /**
     * @ODM\ReferenceOne(targetDocument=Reference::class)
     *
     * @var Reference|null
     */
    public $referenceOne;
}
