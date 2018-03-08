<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use MongoDB\BSON\ObjectId;

class GH816Test extends BaseTest
{
    public function testPersistAfterDetachWithIdSet()
    {
        $d=new GH816Document();
        $d->_id=new ObjectId();
        $this->assertEmpty($this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\GH816Document')->findAll());
        $this->dm->persist($d);
        $this->dm->detach($d);
        $this->dm->flush();
        $this->assertEmpty($this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\GH816Document')->findAll());
    }

    public function testPersistAfterDetachWithTitleSet()
    {
        $d=new GH816Document();
        $d->title='Test';
        $this->assertEmpty($this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\GH816Document')->findAll());
        $this->dm->persist($d);
        $this->dm->detach($d);
        $this->dm->flush();
        $this->assertEmpty($this->dm->getRepository('Doctrine\ODM\MongoDB\Tests\GH816Document')->findAll());
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
