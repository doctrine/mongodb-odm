<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Exception;

use function get_class;

class GH999Test extends BaseTest
{
    public function testModifyingInFlushHandler(): void
    {
        $this->dm->getEventManager()->addEventListener([Events::onFlush], new GH999Listener());

        $document = new GH999Document('name');
        $this->dm->persist($document);
        $this->dm->flush();

        $this->dm->clear();

        $document = $this->dm->find(GH999Document::class, $document->getId());
        $this->assertSame('name #changed', $document->getName());
    }
}

class GH999Listener
{
    public function onFlush(OnFlushEventArgs $args): void
    {
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
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    /** @ODM\PostUpdate */
    public function postUpdate(): void
    {
        throw new Exception('Did not expect postUpdate to be called when persisting a new document');
    }
}
