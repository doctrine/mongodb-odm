<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;

/**
 * AutoGenerator generates a native ObjectId
 */
final class AutoGenerator extends AbstractIdGenerator
{
    public function generate(DocumentManager $dm, object $document)
    {
        return new ObjectId();
    }
}
