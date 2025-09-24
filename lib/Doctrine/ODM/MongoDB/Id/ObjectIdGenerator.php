<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;

/** @internal */
final class ObjectIdGenerator extends AbstractIdGenerator
{
    public function generate(DocumentManager $dm, object $document): ObjectId
    {
        return new ObjectId();
    }
}
