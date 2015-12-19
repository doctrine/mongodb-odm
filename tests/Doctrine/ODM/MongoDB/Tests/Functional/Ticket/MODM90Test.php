<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM90Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function getDocumentManager()
    {
        $this->listener = new MODM90EventListener();
        $evm = $this->dm->getEventManager();
        $events = array(
            Events::preUpdate,
            Events::postUpdate,
        );
        $evm->addEventListener($events, $this->listener);
        return $this->dm;
    }

    public function testDocumentWithEmbeddedDocumentNotUpdatedOnFlush()
    {
        $dm = $this->getDocumentManager();

        $testDoc = new MODM90TestDocument();
        $testDoc->name = 'Parent';
        $testDoc->embedded = new MODM90TestEmbeddedDocument();
        $testDoc->embedded->name = 'Child';
        $dm->persist($testDoc);
        $dm->flush();
        $dm->clear();

        $testDoc = $dm->find(__NAMESPACE__.'\MODM90TestDocument', $testDoc->id);

        // run a flush, in theory, nothing should be flushed.
        $dm->flush();
        $dm->clear();

        // no update events should be called
        $called = array();
        $this->assertEquals($called, $this->listener->called);
    }

    /*
     * Ensures that the descriminator field is not unset if it's a
     * real property on the document.
     */
    public function testDiscriminatorFieldValuePresentIfRealProperty()
    {
        $dm = $this->getDocumentManager();

        $testDoc = new MODM90TestDocument();
        $testDoc->name = 'Parent';
        $testDoc->embedded = new MODM90Test2EmbeddedDocument();
        $testDoc->embedded->name = 'Child';
        $dm->persist($testDoc);
        $dm->flush();
        $dm->clear();

        $testDoc = $dm->find(__NAMESPACE__.'\MODM90TestDocument', $testDoc->id);

        $this->assertEquals($testDoc->embedded->type, 'test2');
    }
}

class MODM90EventListener
{
    public $called = array();
    public function __call($method, $args)
    {
        $document = $args[0]->getDocument();
        $className = get_class($document);
        $this->called[$method][] = $className;
    }
}

/** @ODM\Document */
class MODM90TestDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /**
     * @ODM\EmbedOne
     * (
     *   discriminatorField="type",
     *   discriminatorMap={
     *     "test"="MODM90TestEmbeddedDocument",
     *     "test2"="MODM90Test2EmbeddedDocument"
     *   }
     *  )
     */
    public $embedded;
}

/** @ODM\EmbeddedDocument */
class MODM90TestEmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\EmbeddedDocument */
class MODM90Test2EmbeddedDocument
{
    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\Field(type="string") The discriminator field is a real property */
    public $type;
}
