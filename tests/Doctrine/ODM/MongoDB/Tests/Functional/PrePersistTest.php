<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class PrePersistTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testPrePersist()
    {
        $test = new PrePersistTestDocument();
        $this->dm->persist($test);
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->flush();

        $this->assertEquals(1, $test->prePersist);

        $test->field = 'test';

        $this->dm->flush();
        $this->dm->flush();

        $this->assertEquals(1, $test->preUpdate);
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class PrePersistTestDocument
{
    public $prePersist;
    public $preUpdate;

    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $field;

    /** @ODM\PrePersist */
    public function prePersist()
    {
        $this->prePersist++;
    }

    /** @ODM\PreUpdate */
    public function preUpdate()
    {
        $this->preUpdate++;
    }
}
