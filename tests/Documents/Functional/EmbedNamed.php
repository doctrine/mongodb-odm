<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class EmbedNamed
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedOne(targetDocument=EmbeddedWhichReferences::class, name="embedded_doc")
     *
     * @var EmbeddedWhichReferences|null
     */
    public $embeddedDoc;

    /**
     * @ODM\EmbedMany(targetDocument=EmbeddedWhichReferences::class, name="embedded_docs")
     *
     * @var Collection<int, EmbeddedWhichReferences>|array<EmbeddedWhichReferences>
     */
    public $embeddedDocs = [];
}
