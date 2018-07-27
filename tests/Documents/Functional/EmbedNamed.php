<?php

declare(strict_types=1);

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class EmbedNamed
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument=EmbeddedWhichReferences::class, name="embedded_doc") */
    public $embeddedDoc;

    /** @ODM\EmbedMany(targetDocument=EmbeddedWhichReferences::class, name="embedded_docs") */
    public $embeddedDocs = [];
}
