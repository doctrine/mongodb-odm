<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH816Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testPersistAfterDetachWithIdSet()
    {
        $d=new GH816Document();
        $d->_id=new \MongoId();
        $this->assertSame(array(), $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\GH816Document')->findAll());
        $this->dm->persist($d);
        $this->dm->detach($d);
        $this->dm->flush();
        $this->assertSame(array(), $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\GH816Document')->findAll());
    }
    
    public function testPersistAfterDetachWithTitleSet()
    {
        $d=new GH816Document();
        $d->title="Test";
        $this->assertSame(array(), $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\GH816Document')->findAll());
        $this->dm->persist($d);
        $this->dm->detach($d);
        $this->dm->flush();
        $this->assertSame(array(), $this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\GH816Document')->findAll());
    }
}

/**
 * @ODM\Document
 */
class GH816Document
{
    /** @ODM\Id */
    public $_id;
    
    /** @ODM\Field(type="string") */
    public $title;
}
