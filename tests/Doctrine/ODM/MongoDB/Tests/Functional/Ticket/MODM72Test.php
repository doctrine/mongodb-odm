<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM72Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\MODM72User');
        $this->assertEquals(array('test' => 'test'), $class->fieldMappings['name']['options']);
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
