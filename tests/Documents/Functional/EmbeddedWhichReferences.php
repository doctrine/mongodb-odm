<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\EmbeddedDocument */
class EmbeddedWhichReferences
{
    /**
     * @ODM\ReferenceOne(targetDocument=Reference::class, name="reference_doc")
     *
     * @var Reference|null
     */
    public $referencedDoc;

    /**
     * @ODM\ReferenceMany(targetDocument=Reference::class, name="reference_docs")
     *
     * @var Collection<int, Reference>|array<Reference>
     */
    public $referencedDocs = [];
}
