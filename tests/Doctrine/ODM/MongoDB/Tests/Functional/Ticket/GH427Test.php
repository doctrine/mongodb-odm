<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH427Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $sub = new GH427Sub();
        $this->dm->persist($sub);
        $this->dm->flush();

        $this->assertEquals(1, $sub->prePersistCount);
    }
}

/** @ODM\MappedSuperclass */
abstract class GH427Super
{
    public $prePersistCount;

    /** @ODM\PrePersist */
    public function prePersist()
    {
        $this->prePersistCount++;
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class GH427Sub extends GH427Super
{
    /** @ODM\Id */
    public $id;
}
