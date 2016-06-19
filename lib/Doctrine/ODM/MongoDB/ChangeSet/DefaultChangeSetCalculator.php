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

namespace Doctrine\ODM\MongoDB\ChangeSet;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\MongoDB\GridFSFile;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

/**
 * ChangeSet calculator extracted from UnitOfWork.
 */
class DefaultChangeSetCalculator implements ChangeSetCalculator
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var UnitOfWork
     */
    private $uow;

    /**
     * @param DocumentManager $documentManager
     * @param UnitOfWork $unitOfWork
     */
    public function __construct(DocumentManager $documentManager, UnitOfWork $unitOfWork)
    {
        $this->dm = $documentManager;
        $this->uow = $unitOfWork;
    }

    /**
     * @param object $document
     * @param ClassMetadata $class
     * @param array|null $originalData
     * @param ObjectChangeSet $changeSet previously calculated change set
     * @return ObjectChangeSet
     */
    public function calculate($document, ClassMetadata $class, $originalData = null, ObjectChangeSet $changeSet = null)
    {
        if ($changeSet === null) {
            $changeSet = new ObjectChangeSet($document, []);
        }
        $actualData = $this->getDocumentActualData($document, $class);
        if ($originalData === null) {
            // Document is either NEW or MANAGED but not yet fully persisted (only has an id). These result in an INSERT.
            foreach ($actualData as $propName => $actualValue) {
                // PersistentCollection shouldn't be here, probably it was cloned and its ownership must be fixed
                if ($actualValue instanceof PersistentCollectionInterface && $actualValue->getOwner() !== $document) {
                    $actualData[$propName] = $this->uow->fixPersistentCollectionOwnership($actualValue, $document, $class, $propName);
                    $actualValue = $actualData[$propName];
                }
                // ignore inverse side of reference relationship
                if (! empty($class->fieldMappings[$propName]['isInverseSide'])) {
                    continue;
                }
                $changeSet[$propName] = new FieldChange(null, $actualValue);
            }
            return $changeSet;
        }
        // Document is "fully" MANAGED: it was already fully persisted before and we have a copy of the original data
        foreach ($actualData as $propName => $actualValue) {
            // skip not saved fields
            if (! empty($class->fieldMappings[$propName]['notSaved'])) {
                continue;
            }

            $orgValue = isset($originalData[$propName]) ? $originalData[$propName] : null;

            // skip if value has not changed
            if ($orgValue === $actualValue) {
                if ($actualValue instanceof PersistentCollectionInterface) {
                    if (! $actualValue->isDirty() && ! $this->uow->isCollectionScheduledForDeletion($actualValue)) {
                        // consider dirty collections as changed as well
                        continue;
                    }
                } elseif ( ! (isset($class->fieldMappings[$propName]['file']) && $actualValue->isDirty())) {
                    // but consider dirty GridFSFile instances as changed
                    continue;
                }
            }

            // if relationship is a embed-one, schedule orphan removal to trigger cascade remove operations
            if (isset($class->fieldMappings[$propName]['embedded']) && $class->fieldMappings[$propName]['type'] === 'one') {
                if ($orgValue !== null) {
                    $this->uow->scheduleOrphanRemoval($orgValue);
                }

                $changeSet[$propName] = new FieldChange($orgValue, $actualValue);
                continue;
            }

            // if owning side of reference-one relationship
            if (isset($class->fieldMappings[$propName]['reference']) && $class->fieldMappings[$propName]['type'] === 'one' && $class->fieldMappings[$propName]['isOwningSide']) {
                if ($orgValue !== null && $class->fieldMappings[$propName]['orphanRemoval']) {
                    $this->uow->scheduleOrphanRemoval($orgValue);
                }

                $changeSet[$propName] = new FieldChange($orgValue, $actualValue);
                continue;
            }

            // ignore the rest for change notifying documents and inverse side of reference relationship
            if ($class->isChangeTrackingNotify() || ! empty($class->fieldMappings[$propName]['isInverseSide'])) {
                continue;
            }

            // Persistent collection was exchanged with the "originally" created one. This can only mean it was cloned
            // and replaced on another document.
            if ($actualValue instanceof PersistentCollectionInterface && $actualValue->getOwner() !== $document) {
                $actualValue = $this->uow->fixPersistentCollectionOwnership($actualValue, $document, $class, $propName);
            }

            // if embed-many or reference-many relationship
            if (isset($class->fieldMappings[$propName]['type']) && $class->fieldMappings[$propName]['type'] === ClassMetadata::MANY) {
                $changeSet[$propName] = $orgValue === $actualValue
                    ? new CollectionChangeSet($actualValue, [])
                    : new FieldChange($orgValue, $actualValue);
                /* If original collection was exchanged with a non-empty value
                 * and $set will be issued, there is no need to $unset it first
                 */
                if ($actualValue && $actualValue->isDirty() && CollectionHelper::usesSet($class->fieldMappings[$propName]['strategy'])) {
                    continue;
                }
                if ($orgValue !== $actualValue && $orgValue instanceof PersistentCollectionInterface) {
                    $this->uow->scheduleCollectionDeletion($orgValue);
                }
                continue;
            }

            // skip equivalent date values
            if (isset($class->fieldMappings[$propName]['type']) && $class->fieldMappings[$propName]['type'] === 'date') {
                $dateType = Type::getType('date');
                $dbOrgValue = $dateType->convertToDatabaseValue($orgValue);
                $dbActualValue = $dateType->convertToDatabaseValue($actualValue);

                if ($dbOrgValue instanceof \MongoDate && $dbActualValue instanceof \MongoDate && $dbOrgValue == $dbActualValue) {
                    continue;
                }
            }

            // regular field
            $changeSet[$propName] = new FieldChange($orgValue, $actualValue);
        }
        return $changeSet;
    }

    /**
     * Get a documents actual data, flattening all the objects to arrays.
     *
     * @param object $document
     * @param ClassMetadata $class
     * @return array
     */
    public function getDocumentActualData($document, ClassMetadata $class)
    {
        $actualData = array();
        foreach ($class->reflFields as $name => $refProp) {
            $mapping = $class->fieldMappings[$name];
            // skip not saved fields
            if (isset($mapping['notSaved']) && $mapping['notSaved'] === true) {
                continue;
            }
            $value = $refProp->getValue($document);
            if (isset($mapping['file']) && ! $value instanceof GridFSFile) {
                $value = new GridFSFile($value);
                $class->reflFields[$name]->setValue($document, $value);
                $actualData[$name] = $value;
            } elseif ((isset($mapping['association']) && $mapping['type'] === 'many')
                && $value !== null && ! ($value instanceof PersistentCollectionInterface)) {
                // If $actualData[$name] is not a Collection then use an ArrayCollection.
                if ( ! $value instanceof Collection) {
                    $value = new ArrayCollection($value);
                }

                // Inject PersistentCollection
                $coll = $this->dm->getConfiguration()->getPersistentCollectionFactory()->create($this->dm, $mapping, $value);
                $coll->setOwner($document, $mapping);
                $coll->setDirty( ! $value->isEmpty());
                $class->reflFields[$name]->setValue($document, $coll);
                $actualData[$name] = $coll;
            } else {
                $actualData[$name] = $value;
            }
        }
        return $actualData;
    }
}
