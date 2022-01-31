<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MODM72Test extends BaseTest
{
    public function testTest(): void
    {
        $class = $this->dm->getClassMetadata(MODM72User::class);
        $this->assertEquals(['test' => 'test'], $class->fieldMappings['name']['options']);
    }
}

/** @ODM\Document */
class MODM72User
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string", options={"test"="test"})
     *
     * @var string|null
     */
    public $name;
}
