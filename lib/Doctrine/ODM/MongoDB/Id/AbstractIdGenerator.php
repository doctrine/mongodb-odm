<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * AbstractIdGenerator
 */
abstract class AbstractIdGenerator
{
    /**
     * Generates an identifier for a document.
     *
     * @return mixed
     */
    abstract public function generate(DocumentManager $dm, object $document);
}
