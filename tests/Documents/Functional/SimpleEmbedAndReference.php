<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'functional_tests')]
class SimpleEmbedAndReference
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var Collection<int, Embedded>|array<Embedded> */
    #[ODM\EmbedMany(targetDocument: Embedded::class)]
    public $embedMany = [];

    /** @var Collection<int, Reference>|array<Reference> */
    #[ODM\ReferenceMany(targetDocument: Reference::class)]
    public $referenceMany = [];

    /** @var Embedded|null */
    #[ODM\EmbedOne(targetDocument: Embedded::class)]
    public $embedOne;

    /** @var Reference|null */
    #[ODM\ReferenceOne(targetDocument: Reference::class)]
    public $referenceOne;
}
