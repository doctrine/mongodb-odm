<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\EmbeddedDocument]
class EmbeddedWhichReferences
{
    /** @var Reference|null */
    #[ODM\ReferenceOne(targetDocument: Reference::class, name: 'reference_doc')]
    public $referencedDoc;

    /** @var Collection<int, Reference>|array<Reference> */
    #[ODM\ReferenceMany(targetDocument: Reference::class, name: 'reference_docs')]
    public $referencedDocs = [];
}
