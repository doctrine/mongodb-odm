<?php

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * AbstractIdGenerator
 *
 * @since       1.0
 */
abstract class AbstractIdGenerator
{
    /**
     * Generates an identifier for a document.
     *
     * @param \Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param object $document
     * @return mixed
     */
    abstract public function generate(DocumentManager $dm, $document);
}
