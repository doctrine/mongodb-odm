<?php

namespace Documents\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @ODM\Document */
class EmbedNamed
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="EmbeddedWhichReferences", name="embedded_doc") */
    public $embeddedDoc;

    /** @ODM\EmbedMany(targetDocument="EmbeddedWhichReferences", name="embedded_docs") */
    public $embeddedDocs = [];
}
