<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH301Test extends BaseTest
{
    public function testPersistIsCascadedInSingleDocumentFlush()
    {
        $ref = new GH301Document();
        $ref->name = 'referenced';

        $doc = new GH301Document();
        $doc->name = 'parent';
        $doc->refOnePersist = $ref;

        $this->dm->persist($doc);
        $this->dm->flush($doc);
        $this->dm->clear();

        $docId = $doc->id; unset($doc);
        $refId = $ref->id; unset($ref);

        $doc = $this->dm->find(__NAMESPACE__ . '\GH301Document', $docId);
        $this->assertNotNull($doc);
        $this->assertEquals($refId, $doc->refOnePersist->id);
    }
}

/** @ODM\Document */
class GH301Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument="GH301Document", cascade="persist")
     */
    public $refOnePersist;
}
