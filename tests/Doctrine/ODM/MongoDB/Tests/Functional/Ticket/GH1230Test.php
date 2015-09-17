<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1230Test extends BaseTest
{
    public function testEmbeddedDocChangesetInPreUpdateHook()
    {
        $doc = new GH1230Document();
        $doc->embeddedDoc = new GH1230EmbeddedDocument('embedded doc value');
        $doc->stringProperty = 'string property';

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        /** @var GH1230Document $doc */
        $doc = $this->dm->getRepository(get_class($doc))->find($doc->id);
        $doc->embeddedDoc->value = 'updated embedded doc value';
        $doc->stringProperty = 'updated string property';

        $doc->test = $this;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();
    }

    public function getDm()
    {
        return $this->dm;
    }
}

/**
 * @ODM\Document
 * @ODM\HasLifecycleCallbacks
 */
class GH1230Document
{
    /** @ODM\Id */
    public $id;
    /** @ODM\EmbedOne(targetDocument="GH1230EmbeddedDocument") */
    public $embeddedDoc;
    /** @ODM\String */
    public $stringProperty;

    /** @var GH1230Test */
    public $test;

    /**
     * @ODM\PreUpdate
     */
    public function preUpdateHook()
    {
        $this->test->assertEquals(
            array(
                'stringProperty' => array(
                    'string property',
                    'updated string property',
                ),
                'embeddedDoc'    => array(
                    new GH1230EmbeddedDocument('embedded doc value'),
                    new GH1230EmbeddedDocument('updated embedded doc value'),
                ),
            ),
            $this->test->getDm()->getUnitOfWork()->getDocumentChangeSet($this)
        );
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class GH1230EmbeddedDocument
{
    /** @ODM\String */
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
