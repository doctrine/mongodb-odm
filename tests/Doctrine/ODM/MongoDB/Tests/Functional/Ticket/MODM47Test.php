<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

class MODM47Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $this->dm->getMongo()->modm47_test->a->insert(array(
            'c' => 'c value'
        ));

        $a = $this->dm->findOne(__NAMESPACE__.'\MODM47A');
        $this->assertEquals('c value', $a->b);
    }
}

/** @Document(db="modm47_test", collection="a") */
class MODM47A
{
    /** @Id */
    public $id;

    /** @String */
    public $b = 'tmp';

    /** @AlsoLoad("c") */
    function renameC($c) {$this->b = $c;}
    function getId() {return $this->id;}
}