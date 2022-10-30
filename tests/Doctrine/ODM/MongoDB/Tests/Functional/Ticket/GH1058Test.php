<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Exception;
use MongoDB\BSON\ObjectId;

use function array_merge;
use function get_class;

class GH1058Test extends BaseTest
{
    /** @doesNotPerformAssertions */
    public function testModifyingDuringOnFlushEventNewDocument(): void
    {
        $this->dm->getEventManager()->addEventListener([Events::onFlush], new GH1058Listener());
        $document = new GH1058PersistDocument();
        $document->setValue('value 1');
        $this->dm->persist($document);
        $this->dm->flush();
    }

    /** @doesNotPerformAssertions */
    public function testModifyingDuringOnFlushEventNewDocumentWithId(): void
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
    public function onFlush(OnFlushEventArgs $args): void
    {
        $dm  = $args->getDocumentManager();
        $uow = $dm->getUnitOfWork();

        foreach (array_merge($uow->getScheduledDocumentInsertions(), $uow->getScheduledDocumentUpserts()) as $document) {
            $document->setValue('value 2');
            $metadata = $dm->getClassMetadata(get_class($document));
            $dm->getUnitOfWork()->recomputeSingleDocumentChangeSet($metadata, $document);

            if ($uow->isScheduledForUpdate($document)) {
                throw new Exception('Document should not be scheduled for update!');
            }
        }
    }
}

/** @ODM\Document */
class GH1058PersistDocument
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
    private $value;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}

/** @ODM\Document */
class GH1058UpsertDocument
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
    private $value;

    public function getId(): ?string
    {
        return $this->id;
    }

    final public function generateId(): void
    {
        if (isset($this->id)) {
            return;
        }

        $this->id = (string) new ObjectId();
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }
}
