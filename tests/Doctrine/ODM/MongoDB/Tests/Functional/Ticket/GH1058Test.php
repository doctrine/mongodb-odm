<?php
namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;
use Doctrine\ODM\MongoDB\Event\OnFlushEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
class GH1058Test extends BaseTest
{
    public function testModifyingDuringOnFlushEventNewDocument()
    {
        $this->dm->getEventManager()->addEventListener(array(Events::onFlush), new GH1058Listener());
        $document = new GH1058PersistDocument();
        $document->setValue('value 1');
        $this->dm->persist($document);
        $this->dm->flush();
    }
    public function testModifyingDuringOnFlushEventNewDocumentWithId()
    {
        $this->dm->getEventManager()->addEventListener(array(Events::onFlush), new GH1058Listener());
        $document = new GH1058UpsertDocument();
        $document->generateId();
        $document->setValue('value 1');
        $this->dm->persist($document);
        $this->dm->flush();
    }
}
class GH1058Listener
{
    public function onFlush(OnFlushEventArgs $args) {
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
    public final function generateId()
    {
        if (!isset($this->id)) {
            $this->id = (string) new \MongoId();
        }
    }
    public function setValue($value)
    {
        $this->value = $value;
    }
}

