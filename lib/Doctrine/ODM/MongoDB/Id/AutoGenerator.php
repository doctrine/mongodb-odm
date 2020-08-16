<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManagerInterface;
use MongoDB\BSON\ObjectId;

/**
 * AutoGenerator generates a native ObjectId
 */
final class AutoGenerator extends AbstractIdGenerator
{
    /** @inheritDoc */
    public function generate(DocumentManagerInterface $dm, object $document)
    {
        return new ObjectId();
    }
}
