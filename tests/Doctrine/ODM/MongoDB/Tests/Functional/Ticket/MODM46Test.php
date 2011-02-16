<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

class MODM46Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $a = array(
            'c' => array('value' => 'value')
        );
        $this->dm->getConnection()->modm46_test->a->insert($a);

        $a = $this->dm->find(__NAMESPACE__.'\MODM46A', $a['_id']);

        $this->assertTrue(isset($a->b));
        $this->assertEquals('value', $a->b->value);
    }
}

/** @Document(db="modm46_test", collection="a") */
class MODM46A
{
    /** @Id */
    public $id;

    /**
     * @EmbedOne(targetDocument="MODM46AB")
     * @AlsoLoad("c")
     */
    public $b;
}

/** @EmbeddedDocument */
class MODM46AB
{
    /** @String */
    public $value;
}