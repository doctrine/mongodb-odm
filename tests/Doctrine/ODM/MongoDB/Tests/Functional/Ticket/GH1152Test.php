<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH1152Test extends BaseTestCase
{
    public function testParentAssociationsInPostLoad(): void
    {
        $listener = new GH1152Listener();
        $this->dm->getEventManager()->addEventListener(Events::postLoad, $listener);

        $parent        = new GH1152Parent();
        $parent->child = new GH1152Child();

        $this->dm->persist($parent);
        $this->dm->flush();

        $this->dm->clear();

        $parent = $this->dm->find(GH1152Parent::CLASSNAME, $parent->id);
        self::assertNotNull($parent);

        self::assertNotNull($parent->child->parentAssociation);
        [$mapping, $parentAssociation, $fieldName] = $parent->child->parentAssociation;

        self::assertSame($parent, $parentAssociation);
    }
}

/** @ODM\Document */
class GH1152Parent
{
    public const CLASSNAME = self::class;

    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedOne(targetDocument=GH1152Child::class)
     *
     * @var GH1152Child|null
     */
    public $child;
}

/**
 * @ODM\EmbeddedDocument
 *
 * @psalm-import-type AssociationFieldMapping from ClassMetadata
 */
class GH1152Child
{
    /** @psalm-var array{0: AssociationFieldMapping, 1: object|null, 2: string}|null */
    public $parentAssociation;
}

class GH1152Listener
{
    public function postLoad(LifecycleEventArgs $args): void
    {
        $dm       = $args->getDocumentManager();
        $document = $args->getDocument();

        if (! $document instanceof GH1152Child) {
            return;
        }

        $document->parentAssociation = $dm->getUnitOfWork()->getParentAssociation($document);
    }
}
