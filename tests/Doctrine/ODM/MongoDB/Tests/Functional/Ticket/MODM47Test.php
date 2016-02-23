<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM47Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $a = array(
            'c' => 'c value'
        );
        $this->dm->getDocumentCollection(__NAMESPACE__.'\MODM47A')->insert($a);

        $a = $this->dm->find(__NAMESPACE__.'\MODM47A', $a['_id']);
        $this->assertEquals('c value', $a->b);
    }
}

/** @ODM\Document */
class MODM47A
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $b = 'tmp';

    /** @ODM\AlsoLoad("c") */
    function renameC($c) {$this->b = $c;}
    function getId() {return $this->id;}
}
