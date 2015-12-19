<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\MongoDB\GridFSFile;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH832Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @expectedException MongoGridFSException
     */
    public function testGridFSWithUniqueIndex()
    {
        $doc = new GH832Document();
        $this->dm->getSchemaManager()->ensureDocumentIndexes(get_class($doc));

        $docA = new GH832Document();
        $docA->unique = 'foo';
        $docA->file = new GridFSFile(__FILE__);

        $this->dm->persist($docA);
        $this->dm->flush();
        $this->dm->clear();

        $docB = new GH832Document();
        $docB->unique = 'foo';
        $docB->file = new GridFSFile(__FILE__);

        $this->dm->persist($docB);
        $this->dm->flush();
        $this->dm->clear();
    }
}

/** @ODM\Document */
class GH832Document
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\File */
    public $file;

    /**
     * @ODM\Field(type="string")
     * @ODM\UniqueIndex
     */
    public $unique;
}
