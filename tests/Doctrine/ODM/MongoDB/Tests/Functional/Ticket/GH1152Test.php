<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH1152Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testParentAssociationsInPostLoad()
    {
        $listener = new GH1152Listener();
        $this->dm->getEventManager()->addEventListener(Events::postLoad, $listener);

        $parent = new GH1152Parent();
        $parent->child = new GH1152Child();

        $this->dm->persist($parent);
        $this->dm->flush();

        $this->dm->clear();

        $parent = $this->dm->find(GH1152Parent::CLASSNAME, $parent->id);
        $this->assertNotNull($parent);

        $this->assertNotNull($parent->child->parentAssociation);
        list($mapping, $parentAssociation, $fieldName) = $parent->child->parentAssociation;

        $this->assertSame($parent, $parentAssociation);
    }
}

/** @ODM\Document */
class GH1152Parent
{
    const CLASSNAME = __CLASS__;

    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="GH1152Child") */
    public $child;
}

/** @ODM\EmbeddedDocument */
class GH1152Child
{
    public $parentAssociation;
}

class GH1152Listener
{
    public function postLoad(LifecycleEventArgs $args)
    {
        $dm = $args->getDocumentManager();
        $document = $args->getDocument();

        if (!$document instanceof GH1152Child) {
            return;
        }

        $document->parentAssociation = $dm->getUnitOfWork()->getParentAssociation($document);
    }
}
