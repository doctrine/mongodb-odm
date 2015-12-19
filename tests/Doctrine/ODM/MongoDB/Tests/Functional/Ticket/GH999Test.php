<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH999Test extends BaseTest
{
    public function testModifyingInFlushHandler()
    {
        $this->dm->getEventManager()->addEventListener(array(Events::onFlush), new GH999Listener());

        $document = new GH999Document('name');
        $this->dm->persist($document);
        $this->dm->flush();
    }
}

class GH999Listener
{
    public function onFlush(OnFlushEventArgs $args) {
        $dm = $args->getDocumentManager();

        foreach ($dm->getUnitOfWork()->getScheduledDocumentInsertions() as $document) {
            $document->setName('name #changed');
            $metadata = $dm->getClassMetadata(get_class($document));
            $dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($metadata, $document);
        }
    }
}

/** @ODM\Document @ODM\HasLifecycleCallbacks */
class GH999Document
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    /** @ODM\PostUpdate */
    public function postUpdate()
    {
        throw new \Exception('Did not expect postUpdate to be called when persisting a new document');
    }
}

