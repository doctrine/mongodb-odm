<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH850Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You are trying to reference a non-object in refs field, "" given
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
