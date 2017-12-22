<?php

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * AutoGenerator generates a native ObjectId
 *
 * @since       1.0
 */
class AutoGenerator extends AbstractIdGenerator
{
    /** @inheritDoc */
    public function generate(DocumentManager $dm, $document)
    {
        return new \MongoDB\BSON\ObjectId();
    }
}
