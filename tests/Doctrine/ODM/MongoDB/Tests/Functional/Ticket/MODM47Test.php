<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

class MODM47Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $a = array(
            '_id' => new ObjectId(),
            'c' => 'c value'
        );
        $this->dm->getDocumentCollection(__NAMESPACE__.'\MODM47A')->insertOne($a);

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
