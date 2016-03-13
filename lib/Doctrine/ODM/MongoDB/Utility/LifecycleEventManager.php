<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Utility;

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

/**
 * @internal
 * @since 1.1
 */
class LifecycleEventManager
{
    /**
     * @var DocumentManager
     */
    private $dm;
    
    /**
     * @var EventManager
     */
    private $evm;

    /**
     * @var UnitOfWork
     */
    private $uow;

    /**
     * @param DocumentManager $dm
     * @param UnitOfWork $uow
     * @param EventManager $evm
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow, EventManager $evm)
    {
        $this->dm = $dm;
        $this->evm = $evm;
        $this->uow = $uow;
    }

    /**
     * @param object $proxy
     * @param mixed $id
     * @return bool Returns whether the exceptionDisabled flag was set
     */
    public function documentNotFound($proxy, $id)
    {
        $eventArgs = new DocumentNotFoundEventArgs($proxy, $this->dm, $id);
        $this->evm->dispatchEvent(Events::documentNotFound, $eventArgs);

        return $eventArgs->isExceptionDisabled();
    }

    /**
     * Dispatches postCollectionLoad event.
     *
     * @param PersistentCollectionInterface $coll
     */
    public function postCollectionLoad(PersistentCollectionInterface $coll)
    {
        $eventArgs = new PostCollectionLoadEventArgs($coll, $this->dm);
        $this->evm->dispatchEvent(Events::postCollectionLoad, $eventArgs);
    }

    /**
     * Invokes postPersist callbacks and events for given document cascading them to embedded documents as well.
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    public function postPersist(ClassMetadata $class, $document)
    {
        $class->invokeLifecycleCallbacks(Events::postPersist, $document, array(new LifecycleEventArgs($document, $this->dm)));
        $this->evm->dispatchEvent(Events::postPersist, new LifecycleEventArgs($document, $this->dm));
        $this->cascadePostPersist($class, $document);
    }

    /**
     * Invokes postRemove callbacks and events for given document.
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    public function postRemove(ClassMetadata $class, $document)
    {
        $class->invokeLifecycleCallbacks(Events::postRemove, $document, array(new LifecycleEventArgs($document, $this->dm)));
        $this->evm->dispatchEvent(Events::postRemove, new LifecycleEventArgs($document, $this->dm));
    }

    /**
     * Invokes postUpdate callbacks and events for given document. The same will be done for embedded documents owned
     * by given document unless they were new in which case postPersist callbacks and events will be dispatched.
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    public function postUpdate(ClassMetadata $class, $document)
    {
        $class->invokeLifecycleCallbacks(Events::postUpdate, $document, array(new LifecycleEventArgs($document, $this->dm)));
        $this->evm->dispatchEvent(Events::postUpdate, new LifecycleEventArgs($document, $this->dm));
        $this->cascadePostUpdate($class, $document);
    }

    /**
     * Invokes prePersist callbacks and events for given document.
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    public function prePersist(ClassMetadata $class, $document)
    {
        $class->invokeLifecycleCallbacks(Events::prePersist, $document, array(new LifecycleEventArgs($document, $this->dm)));
        $this->evm->dispatchEvent(Events::prePersist, new LifecycleEventArgs($document, $this->dm));
    }

    /**
     * Invokes prePersist callbacks and events for given document.
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    public function preRemove(ClassMetadata $class, $document)
    {
        $class->invokeLifecycleCallbacks(Events::preRemove, $document, array(new LifecycleEventArgs($document, $this->dm)));
        $this->evm->dispatchEvent(Events::preRemove, new LifecycleEventArgs($document, $this->dm));
    }

    /**
     * Invokes preUpdate callbacks and events for given document cascading them to embedded documents as well.
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    public function preUpdate(ClassMetadata $class, $document)
    {
        if ( ! empty($class->lifecycleCallbacks[Events::preUpdate])) {
            $class->invokeLifecycleCallbacks(Events::preUpdate, $document, array(
                new PreUpdateEventArgs($document, $this->dm, $this->uow->getDocumentChangeSet($document))
            ));
            $this->uow->recomputeSingleDocumentChangeSet($class, $document);
        }
        $this->evm->dispatchEvent(Events::preUpdate, new PreUpdateEventArgs($document, $this->dm, $this->uow->getDocumentChangeSet($document)));
        $this->cascadePreUpdate($class, $document);
    }

    /**
     * Cascades the preUpdate event to embedded documents.
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    private function cascadePreUpdate(ClassMetadata $class, $document)
    {
        foreach ($class->getEmbeddedFieldsMappings() as $mapping) {
            $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
            if ($value === null) {
                continue;
            }
            $values = $mapping['type'] === ClassMetadata::ONE ? array($value) : $value;

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
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    private function cascadePostUpdate(ClassMetadata $class, $document)
    {
        foreach ($class->getEmbeddedFieldsMappings() as $mapping) {
            $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
            if ($value === null) {
                continue;
            }
            $values = $mapping['type'] === ClassMetadata::ONE ? array($value) : $value;

            foreach ($values as $entry) {
                if (empty($this->uow->getDocumentChangeSet($entry)) && ! $this->uow->hasScheduledCollections($entry)) {
                    continue;
                }
                $entryClass = $this->dm->getClassMetadata(get_class($entry));
                $event = $this->uow->isScheduledForInsert($entry) ? Events::postPersist : Events::postUpdate;
                $entryClass->invokeLifecycleCallbacks($event, $entry, array(new LifecycleEventArgs($entry, $this->dm)));
                $this->evm->dispatchEvent($event, new LifecycleEventArgs($entry, $this->dm));

                $this->cascadePostUpdate($entryClass, $entry);
            }
        }
    }

    /**
     * Cascades the postPersist events to embedded documents.
     *
     * @param ClassMetadata $class
     * @param object $document
     */
    private function cascadePostPersist(ClassMetadata $class, $document)
    {
        foreach ($class->getEmbeddedFieldsMappings() as $mapping) {
            $value = $class->reflFields[$mapping['fieldName']]->getValue($document);
            if ($value === null) {
                continue;
            }
            $values = $mapping['type'] === ClassMetadata::ONE ? array($value) : $value;
            foreach ($values as $embeddedDocument) {
                $this->postPersist($this->dm->getClassMetadata(get_class($embeddedDocument)), $embeddedDocument);
            }
        }
    }
}
