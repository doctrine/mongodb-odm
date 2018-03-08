<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH435Test extends BaseTest
{
    public function testOverridingFieldsType()
    {
        $parent = $this->dm->getClassMetadata(__NAMESPACE__ . '\GH435Parent');

        $this->assertArrayHasKey('id', $parent->fieldMappings['id']);
        $this->assertTrue($parent->fieldMappings['id']['id']);
        $this->assertEquals('id', $parent->fieldMappings['id']['type']);
        $this->assertEquals('int', $parent->fieldMappings['test']['type']);

        $child = $this->dm->getClassMetadata(__NAMESPACE__ . '\GH435Child');

        $this->assertEquals('string', $child->fieldMappings['test']['type']);
    }
}

/** @ODM\Document */
class GH435Parent
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="int") */
    protected $test;
}

/** @ODM\Document */
class GH435Child extends GH435Parent
{
    /** @ODM\Field(type="string") */
    protected $test;
}
