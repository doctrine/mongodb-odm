<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class AtomicManyUpdatesTest extends BaseTest
{
    public function testAtomicManyUpdates()
    {
        $doc = new AtomicManyUpdateDocument();
        $doc->embedMany[] = new AtomicManyUpdateEmbeddedDocument();
        $doc->embedMany[] = new AtomicManyUpdateEmbeddedDocument();

        $this->dm->persist($doc);
        $this->dm->flush();

        $data = $this->getAtomicManyUpdateDocumentData($doc);
        $this->assertCount(2, $data['embedMany']);

        unset($doc->embedMany[1]);

        $this->dm->flush();

        $data = $this->getAtomicManyUpdateDocumentData($doc);
        $this->assertCount(1, $data['embedMany']);

        $doc->embedMany->clear();

        $this->dm->flush();

        $data = $this->getAtomicManyUpdateDocumentData($doc);
        $this->assertFalse(isset($data['embedMany']));
    }

    private function getAtomicManyUpdateDocumentData(AtomicManyUpdateDocument $atomicManyUpdateDocument)
    {
        return $this->dm->getDocumentCollection('Doctrine\ODM\MongoDB\Tests\Functional\AtomicManyUpdateDocument')
            ->findOne(array('_id' => new \MongoId($atomicManyUpdateDocument->id)));;
    }
}

/**
 * @ODM\Document
 */
class AtomicManyUpdateDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument="AtomicManyUpdateEmbeddedDocument", strategy="atomic") */
    public $embedMany = array();
}

/**
 * @ODM\EmbeddedDocument
 */
class AtomicManyUpdateEmbeddedDocument
{
    /** @ODM\Id */
    public $test;
}
