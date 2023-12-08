<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH1107Test extends BaseTestCase
{
    public function testOverrideIdStrategy(): void
    {
        $childObj       = new GH1107ChildClass();
        $childObj->name = 'ChildObject';
        $this->dm->persist($childObj);
        $this->dm->flush();
        self::assertNotNull($childObj->id);
    }
}

#[ODM\Document]
#[ODM\InheritanceType('SINGLE_COLLECTION')]
class GH1107ParentClass
{
    /** @var string|null */
    #[ODM\Id(strategy: 'NONE')]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
}

#[ODM\Document]
class GH1107ChildClass extends GH1107ParentClass
{
    /** @var string|null */
    #[ODM\Id(strategy: 'AUTO')]
    public $id;
}
