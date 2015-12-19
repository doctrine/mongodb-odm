<?php

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1107Test extends BaseTest
{
    public function testOverrideIdStrategy()
    {
        $childObj = new GH1107ChildClass();
        $childObj->name = 'ChildObject';
        $this->dm->persist($childObj);
        $this->dm->flush();
        $this->assertNotNull($childObj->id);
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 */
class GH1107ParentClass
{
    /** @ODM\Id(strategy="NONE") */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\Document */
class GH1107ChildClass extends GH1107ParentClass
{
    /** @ODM\Id(strategy="AUTO") */
    public $id;
}
