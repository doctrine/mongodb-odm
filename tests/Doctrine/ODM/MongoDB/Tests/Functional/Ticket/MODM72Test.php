<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MODM72Test extends BaseTest
{
    public function testTest()
    {
        $class = $this->dm->getClassMetadata(MODM72User::class);
        $this->assertEquals(['test' => 'test'], $class->fieldMappings['name']['options']);
    }
}

/** @ODM\Document */
class MODM72User
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string", options={"test"="test"}) */
    public $name;
}
