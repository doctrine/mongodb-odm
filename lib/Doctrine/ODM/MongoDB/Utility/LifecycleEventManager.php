<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Utility;

use Doctrine\Common\EventArgs;
use Doctrine\Common\EventManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Event\DocumentNotFoundEventArgs;
use Doctrine\ODM\MongoDB\Event\LifecycleEventArgs;
use Doctrine\ODM\MongoDB\Event\PostCollectionLoadEventArgs;
use Doctrine\ODM\MongoDB\Event\PreUpdateEventArgs;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\UnitOfWork;

use function get_class;

/**
 * @internal
 */
final class LifecycleEventManager
{
    /** @var DocumentManager */
    private $dm;

    /** @var EventManager */
    private $evm;

    /** @var UnitOfWork */
    private $uow;

    public function __construct(DocumentManager $dm, UnitOfWork $uow, EventManager $evm)
    {
        $this->dm  = $dm;
        $this->evm = $evm;
        $this->uow = $uow;
    }

    /**
     * @param mixed $id
     *
     * @return bool Returns whether the exceptionDisabled flag was set
     */
    public function documentNotFound(object $proxy, $id): bool
    {
        $eventArgs = new DocumentNotFoundEventArgs($proxy, $this->dm, $id);
        $this->evm->dispatchEvent(Events::documentNotFound, $eventArgs);

        return $eventArgs->isExceptionDisabled();
    }

    /**
     * Dispatches postCollectionLoad event.
     */
    public function postCollectionLoad(PersistentCollectionInterface $coll): void
    {
        $eventArgs = new PostCollectionLoadEventArgs($coll, $this->dm);
        $this->evm->dispatchEvent(Events::postCollectionLoad, $eventArgs);
    }

    /**
     * Invokes postPersist callbacks and events for given document cascading them to embedded documents as well.
     */
    public function postPersist(ClassMetadata $class, object $document): void
    {
        $class->invokeLifecycleCallbacks(Events::postPersist, $document, [new LifecycleEventArgs($document, $this->dm)]);
        $this->dispatchEvent($class, Events::postPersist, new LifecycleEventArgs($document, $this->dm));
        $this->cascadePostPersist($class, $document);
    }

    /**
     * Invokes postRemove callbacks and events for given document.
     */
    public function postRemove(ClassMetadata $class, object $document): void
    {
        $class->invokeLifecycleCallbacks(Events::postRemove, $document, [new LifecycleEventArgs($document, $this->dm)]);
        $this->dispatchEvent($class, Events::postRemove, new LifecycleEventArgs($document, $this->dm));
    }

    /**
     * Invokes postUpdate callbacks and events for given document. The same will be done for embedded documents owned
     * by given document unless they were new in which case postPersist callbacks and events will be dispatched.
     */
    public function postUpdate(ClassMetadata $class, object $document): void
    {
        $class->invokeLifecycleCallbacks(Events::postUpdate, $document, [new LifecycleEventArgs($document, $this->dm)]);
        $this->dispatchEvent($class, Events::postUpdate, new LifecycleEventArgs($document, $this->dm));
        $this->cascadePostUpdate($class, $document);
    }

    /**
     * Invokes prePersist callbacks and events for given document.
     */
    public function prePersist(ClassMetadata $class, object $document): void
    {
        $class->invokeLifecycleCallbacks(Events::prePersist, $document, [new LifecycleEventArgs($document, $this->dm)]);
        $this->dispatchEvent($class, Events::prePersist, new LifecycleEventArgs($document, $this->dm));
    }

    /**
     * Invokes prePersist callbacks and events for given document.
     */
    public function preRemove(ClassMetadata $class, object $document): void
    {
        $class->invokeLifecycleCallbacks(Events::preRemove, $document, [new LifecycleEventArgs($document, $this->dm)]);
        $this->dispatchEvent($class, Events::preRemove, new LifecycleEventArgs($document, $this->dm));
    }

    /**
     * Invokes preUpdate callbacks and events for given document cascading them to embedded documents as well.
     */
    public function preUpdate(ClassMetadata $class, object $document): void
    {
        if (! empty($class->lifecycleCallbacks[Events::preUpdate])) {
            $class->invokeLifecycleCallbacks(Events::preUpdate, $document, [new PreUpdateEventArgs($document, $this->dm, $this->uow->getDocumentChangeSet($document))]);
            $this->uow->recomputeSingleDocumentChangeSet($class, $document);
        }

        $this->dispatchEvent($class, Events::preUpdate, new PreUpdateEventArgs($document, $this->dm, $this->uow->getDocumentChangeSet($document)));
        $this->cascadePreUpdate($class, $document);
    }

    /**
     * Cascades the preUpdate event to embedded documents.
     */
    private function cascadePreUpdate(ClassMetadata $class, object $document): void
    {
        foreach ($class->getEmbeddedFieldsMappings() as $mapping) {
            $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
            if ($value === null) {
                continue;
            }

            $values = $mapping['type'] === ClassMetadata::ONE ? [$value] : $value;

            foreach ($values as $entry) {
                if ($this->uow->isScheduledForInsert($entry) || empty($this->uow->getDocumentChangeSet($entry))) {
                    continue;
                }

                $this->preUpdate($this->dm->getClassMetadata(get_class($entry)), $entry);
            }
        }
    }

    /**
     * Cascades the postUpdate and postPersist events to embedded documents.
     */
    private function cascadePostUpdate(ClassMetadata $class, object $document): void
    {
        foreach ($class->getEmbeddedFieldsMappings() as $mapping) {
            $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
            if ($value === null) {
                continue;
            }

            $values = $mapping['type'] === ClassMetadata::ONE ? [$value] : $value;

            foreach ($values as $entry) {
                if (empty($this->uow->getDocumentChangeSet($entry)) && ! $this->uow->hasScheduledCollections($entry)) {
                    continue;
                }

                $entryClass = $this->dm->getClassMetadata(get_class($entry));
                $event      = $this->uow->isScheduledForInsert($entry) ? Events::postPersist : Events::postUpdate;
                $entryClass->invokeLifecycleCallbacks($event, $entry, [new LifecycleEventArgs($entry, $this->dm)]);
                $this->dispatchEvent($entryClass, $event, new LifecycleEventArgs($entry, $this->dm));

                $this->cascadePostUpdate($entryClass, $entry);
            }
        }
    }

    /**
     * Cascades the postPersist events to embedded documents.
     */
    private function cascadePostPersist(ClassMetadata $class, object $document): void
    {
        foreach ($class->getEmbeddedFieldsMappings() as $mapping) {
            $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
            if ($value === null) {
                continue;
            }

            $values = $mapping['type'] === ClassMetadata::ONE ? [$value] : $value;
            foreach ($values as $embeddedDocument) {
                $this->postPersist($this->dm->getClassMetadata(get_class($embeddedDocument)), $embeddedDocument);
            }
        }
    }

    private function dispatchEvent(ClassMetadata $class, string $eventName, ?EventArgs $eventArgs = null): void
    {
        if ($class->isView()) {
            return;
        }

        $this->evm->dispatchEvent($eventName, $eventArgs);
    }
}
