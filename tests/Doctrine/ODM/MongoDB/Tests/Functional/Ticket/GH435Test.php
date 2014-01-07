<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH435Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $parent = $this->dm->getClassMetadata(__NAMESPACE__ . '\GH435Parent');

        $this->assertArrayHasKey('id', $parent->fieldMappings['id']);
        $this->assertTrue($parent->fieldMappings['id']['id']);
        $this->assertEquals('id', $parent->fieldMappings['id']['type']);
        $this->assertEquals('int', $parent->fieldMappings['test']['type']);

        $child = $this->dm->getClassMetadata(__NAMESPACE__ . '\GH435Child');

        /* The child overrode the identifier field mapping such that the field
         * is no longer an identifier, although the child's ClassMetadata still
         * tracks "id" as its $identifier property and there is a generator.
         * This is a bit weird and should probably be fixed, but it's not what
         * we're testing for here. */
        $this->assertArrayNotHasKey('id', $child->fieldMappings['id']);
        $this->assertEquals('string', $child->fieldMappings['id']['type']);
        $this->assertEquals('string', $child->fieldMappings['test']['type']);
    }
}

/** @ODM\Document */
class GH435Parent
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Int */
    protected $test;
}

/** @ODM\Document */
class GH435Child extends GH435Parent
{
    /** @ODM\String */
    protected $id;

    /** @ODM\String */
    protected $test;
}
