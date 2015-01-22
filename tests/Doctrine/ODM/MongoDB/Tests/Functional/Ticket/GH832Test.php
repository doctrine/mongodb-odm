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
        $this->dm->getSchemaManager()->ensureDocumentIndexes(GH832Document::CLASS);

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
     * @ODM\String
     * @ODM\UniqueIndex
     */
    public $unique;
}
