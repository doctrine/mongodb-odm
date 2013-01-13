<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH435Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $a = $this->dm->getClassMetadata('Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH426A');

        $aTestFieldMappings = $a->fieldMappings['test'];
        $this->assertEquals('int', $aTestFieldMappings['type']);

        $aIdFieldMappings = $a->fieldMappings['id'];   
        $this->assertTrue(isset($aIdFieldMappings['id']));

        $b = $this->dm->getClassMetadata('Doctrine\ODM\MongoDB\Tests\Functional\Ticket\GH426B');

        $bTestFieldMappings = $b->fieldMappings['test'];
        $this->assertEquals('string', $bTestFieldMappings['type']);

        $bIdFieldMappings = $b->fieldMappings['id'];
        $this->assertFalse(isset($bIdFieldMappings['id']));
    }
}

/** @ODM\Document */
class GH426A
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Int */
    protected $test;
}

/** @ODM\Document */
class GH426B extends GH426A
{
    /** @ODM\String */
    protected $id;

    /** @ODM\String */
    protected $test;
}
