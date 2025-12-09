<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class EmbedNamed
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var EmbeddedWhichReferences|null */
    #[ODM\EmbedOne(targetDocument: EmbeddedWhichReferences::class, name: 'embedded_doc')]
    public $embeddedDoc;

    /** @var Collection<int, EmbeddedWhichReferences>|array<EmbeddedWhichReferences> */
    #[ODM\EmbedMany(targetDocument: EmbeddedWhichReferences::class, name: 'embedded_docs')]
    public $embeddedDocs = [];
}
