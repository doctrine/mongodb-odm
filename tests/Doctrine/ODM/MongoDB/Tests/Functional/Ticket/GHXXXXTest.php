<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GHXXXXTest extends BaseTest
{
    public function testEmbeddedDocChangesetInPreUpdateHook()
    {
        $doc = new GHXXXXDocument();
        $doc->embeddedDoc = new GHXXXXEmbeddedDocument('embedded doc value');
        $doc->stringProperty = 'string property';

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        /** @var GHXXXXDocument $doc */
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
class GHXXXXDocument
{
    /** @ODM\Id */
    public $id;
    /** @ODM\EmbedOne(targetDocument="GHXXXXEmbeddedDocument") */
    public $embeddedDoc;
    /** @ODM\String */
    public $stringProperty;

    /** @var GHXXXXTest */
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
                    new GHXXXXEmbeddedDocument('embedded doc value'),
                    new GHXXXXEmbeddedDocument('updated embedded doc value'),
                ),
            ),
            $this->test->getDm()->getUnitOfWork()->getDocumentChangeSet($this)
        );
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class GHXXXXEmbeddedDocument
{
    /** @ODM\String */
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
