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
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\UnitOfWork;
use MongoDB\Driver\Session;

use function spl_object_hash;

/** @internal */
final class LifecycleEventManager
{
    private bool $transactionalModeEnabled = false;

    private ?Session $session = null;

    /** @var array<string, array<string, true>> */
    private array $transactionalEvents = [];

    public function __construct(private DocumentManager $dm, private UnitOfWork $uow, private EventManager $evm)
    {
    }

    public function clearTransactionalState(): void
    {
        $this->transactionalModeEnabled = false;
        $this->session                  = null;
        $this->transactionalEvents      = [];
    }

    public function enableTransactionalMode(Session $session): void
    {
        $this->transactionalModeEnabled = true;
        $this->session                  = $session;
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
     *
     * @phpstan-param PersistentCollectionInterface<array-key, object> $coll
     */
    public function postCollectionLoad(PersistentCollectionInterface $coll): void
    {
        $eventArgs = new PostCollectionLoadEventArgs($coll, $this->dm);
        $this->evm->dispatchEvent(Events::postCollectionLoad, $eventArgs);
    }

    /**
     * Invokes postPersist callbacks and events for given document cascading them to embedded documents as well.
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $document
     *
     * @template T of object
     */
    public function postPersist(ClassMetadata $class, object $document, ?Session $session = null): void
    {
        if (! $this->shouldDispatchEvent($document, Events::postPersist, $session)) {
            return;
        }

        $eventArgs = new LifecycleEventArgs($document, $this->dm, $session);

        $class->invokeLifecycleCallbacks(Events::postPersist, $document, [$eventArgs]);
        $this->dispatchEvent($class, Events::postPersist, $eventArgs);
        $this->cascadePostPersist($class, $document, $session);
    }

    /**
     * Invokes postRemove callbacks and events for given document.
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $document
     *
     * @template T of object
     */
    public function postRemove(ClassMetadata $class, object $document, ?Session $session = null): void
    {
        if (! $this->shouldDispatchEvent($document, Events::postRemove, $session)) {
            return;
        }

        $eventArgs = new LifecycleEventArgs($document, $this->dm, $session);

        $class->invokeLifecycleCallbacks(Events::postRemove, $document, [$eventArgs]);
        $this->dispatchEvent($class, Events::postRemove, $eventArgs);
    }

    /**
     * Invokes postUpdate callbacks and events for given document. The same will be done for embedded documents owned
     * by given document unless they were new in which case postPersist callbacks and events will be dispatched.
     *
     * @phpstan-param ClassMetadata<T> $class
     * @phpstan-param T $document
     *
     * @template T of object
     */
    public function postUpdate(ClassMetadata $class, object $document, ?Session $session = null): void
    {
        if (! $this->shouldDispatchEvent($document, Events::postUpdate, $session)) {
            return;
        }

        $eventArgs = new LifecycleEventArgs($document, $this->dm, $session);

        $class->invokeLifecycleCallbacks(Events::postUpdate, $document, [$eventArgs]);
        $this->dispatchEvent($class, Events::postUpdate, $eventArgs);
        $this->cascadePostUpdate($class, $document, $session);
    }

    /**
     * Invokes prePersist callbacks and events for given document.
     *
     * @param ClassMetadata<T> $class
     * @param T                $document
     *
     * @template T of object
     */
    public function prePersist(ClassMetadata $class, object $document): void
    {
        if (! $this->shouldDispatchEvent($document, Events::prePersist, null)) {
            return;
        }

        $eventArgs = new LifecycleEventArgs($document, $this->dm);

        $class->invokeLifecycleCallbacks(Events::prePersist, $document, [$eventArgs]);
        $this->dispatchEvent($class, Events::prePersist, $eventArgs);
    }

    /**
     * Invokes prePersist callbacks and events for given document.
     *
     * @param ClassMetadata<T> $class
     * @param T                $document
     *
     * @template T of object
     */
    public function preRemove(ClassMetadata $class, object $document): void
    {
        if (! $this->shouldDispatchEvent($document, Events::preRemove, null)) {
            return;
        }

        $eventArgs = new LifecycleEventArgs($document, $this->dm);

        $class->invokeLifecycleCallbacks(Events::preRemove, $document, [$eventArgs]);
        $this->dispatchEvent($class, Events::preRemove, $eventArgs);
    }

    /**
     * Invokes preUpdate callbacks and events for given document cascading them to embedded documents as well.
     *
     * @param ClassMetadata<T> $class
     * @param T                $document
     *
     * @template T of object
     */
    public function preUpdate(ClassMetadata $class, object $document, ?Session $session = null): void
    {
        if (! $this->shouldDispatchEvent($document, Events::preUpdate, $session)) {
            return;
        }

        if (! empty($class->lifecycleCallbacks[Events::preUpdate])) {
            $eventArgs = new PreUpdateEventArgs($document, $this->dm, $this->uow->getDocumentChangeSet($document), $session);
            $class->invokeLifecycleCallbacks(Events::preUpdate, $document, [$eventArgs]);
            $this->uow->recomputeSingleDocumentChangeSet($class, $document);
        }

        $this->dispatchEvent(
            $class,
            Events::preUpdate,
            new PreUpdateEventArgs($document, $this->dm, $this->uow->getDocumentChangeSet($document), $session),
        );
        $this->cascadePreUpdate($class, $document, $session);
    }

    /**
     * Cascades the preUpdate event to embedded documents.
     *
     * @param ClassMetadata<T> $class
     * @param T                $document
     *
     * @template T of object
     */
    private function cascadePreUpdate(ClassMetadata $class, object $document, ?Session $session = null): void
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

                $this->preUpdate($this->dm->getClassMetadata($entry::class), $entry, $session);
            }
        }
    }

    /**
     * Cascades the postUpdate and postPersist events to embedded documents.
     *
     * @param ClassMetadata<T> $class
     * @param T                $document
     *
     * @template T of object
     */
    private function cascadePostUpdate(ClassMetadata $class, object $document, ?Session $session = null): void
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

                $entryClass = $this->dm->getClassMetadata($entry::class);
                $event      = $this->uow->isScheduledForInsert($entry) ? Events::postPersist : Events::postUpdate;

                if (! $this->shouldDispatchEvent($entry, $event, $session)) {
                    continue;
                }

                $eventArgs = new LifecycleEventArgs($entry, $this->dm, $session);

                $entryClass->invokeLifecycleCallbacks($event, $entry, [$eventArgs]);
                $this->dispatchEvent($entryClass, $event, $eventArgs);

                $this->cascadePostUpdate($entryClass, $entry, $session);
            }
        }
    }

    /**
     * Cascades the postPersist events to embedded documents.
     *
     * @param ClassMetadata<T> $class
     * @param T                $document
     *
     * @template T of object
     */
    private function cascadePostPersist(ClassMetadata $class, object $document, ?Session $session = null): void
    {
        foreach ($class->getEmbeddedFieldsMappings() as $mapping) {
            $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
            if ($value === null) {
                continue;
            }

            $values = $mapping['type'] === ClassMetadata::ONE ? [$value] : $value;
            foreach ($values as $embeddedDocument) {
                $this->postPersist($this->dm->getClassMetadata($embeddedDocument::class), $embeddedDocument, $session);
            }
        }
    }

    /** @param ClassMetadata<object> $class */
    private function dispatchEvent(ClassMetadata $class, string $eventName, ?EventArgs $eventArgs = null): void
    {
        if ($class->isView()) {
            return;
        }

        $this->evm->dispatchEvent($eventName, $eventArgs);
    }

    private function shouldDispatchEvent(object $document, string $eventName, ?Session $session): bool
    {
        if (! $this->transactionalModeEnabled) {
            return true;
        }

        if ($session !== $this->session) {
            throw MongoDBException::transactionalSessionMismatch();
        }

        // Check whether the event has already been dispatched.
        $hasDispatched = isset($this->transactionalEvents[spl_object_hash($document)][$eventName]);

        // Mark the event as dispatched - no problem doing this if it already was dispatched
        $this->transactionalEvents[spl_object_hash($document)][$eventName] = true;

        return ! $hasDispatched;
    }
}
