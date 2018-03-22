<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;
use function array_merge;
use function get_class;

class GH1058Test extends BaseTest
{
    /**
     * @doesNotPerformAssertions
     */
    public function testModifyingDuringOnFlushEventNewDocument()
    {
        $this->dm->getEventManager()->addEventListener([Events::onFlush], new GH1058Listener());
        $document = new GH1058PersistDocument();
        $document->setValue('value 1');
        $this->dm->persist($document);
        $this->dm->flush();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testModifyingDuringOnFlushEventNewDocumentWithId()
    {
        $this->dm->getEventManager()->addEventListener([Events::onFlush], new GH1058Listener());
        $document = new GH1058UpsertDocument();
        $document->generateId();
        $document->setValue('value 1');
        $this->dm->persist($document);
        $this->dm->flush();
    }
}

class GH1058Listener
{
    public function onFlush(OnFlushEventArgs $args)
    {
        $dm = $args->getDocumentManager();
        $uow = $dm->getUnitOfWork();

        foreach (array_merge($uow->getScheduledDocumentInsertions(), $uow->getScheduledDocumentUpserts()) as $document) {
            $document->setValue('value 2');
            $metadata = $dm->getClassMetadata(get_class($document));
            $dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($metadata, $document);

            if ($uow->isScheduledForUpdate($document)) {
                throw new \Exception('Document should not be scheduled for update!');
            }
        }
    }
}

/** @ODM\Document */
class GH1058PersistDocument
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $value;

    public function getId()
    {
        return $this->id;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }
}

/** @ODM\Document */
class GH1058UpsertDocument
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $value;

    public function getId()
    {
        return $this->id;
    }

    final public function generateId()
    {
        if (isset($this->id)) {
            return;
        }

        $this->id = (string) new ObjectId();
    }

    public function setValue($value)
    {
        $this->value = $value;
    }
}
