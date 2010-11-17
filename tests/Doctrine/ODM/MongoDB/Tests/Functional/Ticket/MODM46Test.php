<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

require_once __DIR__ . '/../../../../../../TestInit.php';

class MODM46Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $this->dm->getMongo()->modm46_test->a->insert(array(
            'c' => array('value' => 'value')
        ));

        $a = $this->dm->findOne(__NAMESPACE__.'\MODM46A');

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