<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;

interface IdGenerator
{
    /**
     * Generates an identifier for a document.
     */
    public function generate(DocumentManager $dm, object $document): mixed;
}
