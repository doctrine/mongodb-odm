<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH2339Test;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectIdInterface;

/**
 * @ODM\EmbeddedDocument
 */
class InlinedDocument
{
    /** @ODM\Id */
    protected ObjectIdInterface $id;

    public function getId(): ObjectIdInterface
    {
        return $this->id;
    }
}
