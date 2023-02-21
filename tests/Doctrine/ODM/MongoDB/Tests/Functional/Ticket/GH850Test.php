<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use InvalidArgumentException;

class GH850Test extends BaseTest
{
    public function testPersistWrongReference(): void
    {
        $d = new GH850Document();
        $this->dm->persist($d);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected object, found "" in Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH850Document::refs',
        );
        $this->dm->flush();
    }
}

/** @ODM\Document */
class GH850Document
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\ReferenceOne
     *
     * @var object|string
     */
    public $refs = '';
}
