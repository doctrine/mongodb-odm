<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

class MODM72Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\MODM72User');
        $this->assertEquals(array('test' => 'test'), $class->fieldMappings['name']['options']);
    }
}

/** @Document */
class MODM72User
{
    /** @Id */
    public $id;

    /** @String(options={"test"="test"}) */
    public $name;
}