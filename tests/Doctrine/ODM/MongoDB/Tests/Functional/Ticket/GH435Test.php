<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH435Test extends BaseTest
{
    public function testOverridingFieldsType(): void
    {
        $parent = $this->dm->getClassMetadata(GH435Parent::class);

        $this->assertArrayHasKey('id', $parent->fieldMappings['id']);
        $this->assertTrue($parent->fieldMappings['id']['id']);
        $this->assertEquals('id', $parent->fieldMappings['id']['type']);
        $this->assertEquals('int', $parent->fieldMappings['test']['type']);

        $child = $this->dm->getClassMetadata(GH435Child::class);

        $this->assertEquals('string', $child->fieldMappings['test']['type']);
    }
}

/** @ODM\Document */
class GH435Parent
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    /**
     * @ODM\Field(type="int")
     *
     * @var int|string|null
     */
    protected $test;
}

/** @ODM\Document */
class GH435Child extends GH435Parent
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    protected $test;
}
