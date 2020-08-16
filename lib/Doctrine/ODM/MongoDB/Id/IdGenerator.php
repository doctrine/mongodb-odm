<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManagerInterface;

interface IdGenerator
{
    /**
     * Generates an identifier for a document.
     *
     * @return mixed
     */
    public function generate(DocumentManagerInterface $dm, object $document);
}
