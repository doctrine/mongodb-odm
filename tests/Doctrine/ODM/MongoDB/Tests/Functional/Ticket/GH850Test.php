<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH850Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected object, found "" in Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH850Document::refs
     */
    public function testPersistWrongReference()
    {
        $d = new GH850Document();
        $this->dm->persist($d);
        $this->dm->flush();
    }
}

/**
 * @ODM\Document
 */
class GH850Document
{
    /** @ODM\Id */
    public $id;
    
    /** @ODM\ReferenceOne */
    public $refs = "";
}
