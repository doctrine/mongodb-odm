<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;

/**
 * AutoGenerator generates a native ObjectId
 *
 * @deprecated use ObjectIdGenerator instead
 */
final class AutoGenerator extends AbstractIdGenerator
{
    public function generate(DocumentManager $dm, object $document)
    {
        return new ObjectId();
    }
}
