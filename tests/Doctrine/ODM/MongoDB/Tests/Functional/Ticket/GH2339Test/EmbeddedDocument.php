<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH2339Test;

use MongoDB\BSON\ObjectIdInterface;

/**
 * @ODM\EmbeddedDocument
 */
class EmbeddedDocument
{
    /**
     * @ODM\Id
     */
    protected ObjectIdInterface $id;

    public function getId(): ObjectIdInterface
    {
        return $this->id;
    }
}
