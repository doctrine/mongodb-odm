<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH435Test extends BaseTestCase
{
    public function testOverridingFieldsType(): void
    {
        $parent = $this->dm->getClassMetadata(GH435Parent::class);

        self::assertArrayHasKey('id', $parent->fieldMappings['id']);
        self::assertTrue($parent->fieldMappings['id']['id']);
        self::assertEquals('id', $parent->fieldMappings['id']['type']);
        self::assertEquals('int', $parent->fieldMappings['test']['type']);

        $child = $this->dm->getClassMetadata(GH435Child::class);

        self::assertEquals('string', $child->fieldMappings['test']['type']);
    }
}

#[ODM\Document]
class GH435Parent
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;

    /** @var int|string|null */
    #[ODM\Field(type: 'int')]
    protected $test;
}

#[ODM\Document]
class GH435Child extends GH435Parent
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    protected $test;
}
