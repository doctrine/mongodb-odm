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
namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\PersistentCollection\PersistentCollectionInterface;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Doctrine\ODM\MongoDB\Utility\CollectionHelper;

/**
 * PersistenceBuilder builds the queries used by the persisters to update and insert
 * documents when a DocumentManager is flushed. It uses the changeset information in the
 * UnitOfWork to build queries using atomic operators like $set, $unset, etc.
 *
 * @since       1.0
 */
class PersistenceBuilder
{
    /**
     * The DocumentManager instance.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The UnitOfWork instance.
     *
     * @var UnitOfWork
     */
    private $uow;

    /**
     * Initializes a new PersistenceBuilder instance.
     *
     * @param DocumentManager $dm
     * @param UnitOfWork $uow
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow)
    {
        $this->dm = $dm;
        $this->uow = $uow;
    }

    /**
     * Prepares the array that is ready to be inserted to mongodb for a given object document.
     *
     * @param object $document
     * @return array $insertData
     */
    public function prepareInsertData($document)
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        $changeset = $this->uow->getDocumentChangeSet($document);

        $insertData = array();
        foreach ($class->fieldMappings as $mapping) {

            $new = isset($changeset[$mapping['fieldName']][1]) ? $changeset[$mapping['fieldName']][1] : null;

            if ($new === null && $mapping['nullable']) {
                $insertData[$mapping['name']] = null;
            }

            /* Nothing more to do for null values, since we're either storing
             * them (if nullable was true) or not.
             */
            if ($new === null) {
                continue;
            }

            // @Field, @String, @Date, etc.
            if ( ! isset($mapping['association'])) {
                $insertData[$mapping['name']] = Type::getType($mapping['type'])->convertToDatabaseValue($new);

            // @ReferenceOne
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_ONE) {
                $insertData[$mapping['name']] = $this->prepareReferencedDocumentValue($mapping, $new);

            // @EmbedOne
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::EMBED_ONE) {
                $insertData[$mapping['name']] = $this->prepareEmbeddedDocumentValue($mapping, $new);

            // @ReferenceMany, @EmbedMany
            // We're excluding collections using addToSet since there is a risk
            // of duplicated entries stored in the collection
            } elseif ($mapping['type'] === ClassMetadata::MANY && ! $mapping['isInverseSide']
                    && $mapping['strategy'] !== ClassMetadataInfo::STORAGE_STRATEGY_ADD_TO_SET && ! $new->isEmpty()) {
                $insertData[$mapping['name']] = $this->prepareAssociatedCollectionValue($new, true);
            }
        }

        // add discriminator if the class has one
        if (isset($class->discriminatorField)) {
            $insertData[$class->discriminatorField] = isset($class->discriminatorValue)
                ? $class->discriminatorValue
                : $class->name;
        }

        return $insertData;
    }

    /**
     * Prepares the update query to update a given document object in mongodb.
     *
     * @param object $document
     * @return array $updateData
     */
    public function prepareUpdateData($document)
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        $changeset = $this->uow->getDocumentChangeSet($document);

        $updateData = array();
        foreach ($changeset as $fieldName => $change) {
            $mapping = $class->fieldMappings[$fieldName];

            // skip non embedded document identifiers
            if ( ! $class->isEmbeddedDocument && ! empty($mapping['id'])) {
                continue;
            }

            list($old, $new) = $change;

            // Scalar fields
            if ( ! isset($mapping['association'])) {
                if ($new === null && $mapping['nullable'] !== true) {
                    $updateData['$unset'][$mapping['name']] = true;
                } else {
                    if ($new !== null && isset($mapping['strategy']) && $mapping['strategy'] === ClassMetadataInfo::STORAGE_STRATEGY_INCREMENT) {
                        $operator = '$inc';
                        $value = Type::getType($mapping['type'])->convertToDatabaseValue($new - $old);
                    } else {
                        $operator = '$set';
                        $value = $new === null ? null : Type::getType($mapping['type'])->convertToDatabaseValue($new);
                    }

                    $updateData[$operator][$mapping['name']] = $value;
                }

            // @EmbedOne
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::EMBED_ONE) {
                // If we have a new embedded document then lets set the whole thing
                if ($new && $this->uow->isScheduledForInsert($new)) {
                    $updateData['$set'][$mapping['name']] = $this->prepareEmbeddedDocumentValue($mapping, $new);

                // If we don't have a new value then lets unset the embedded document
                } elseif ( ! $new) {
                    $updateData['$unset'][$mapping['name']] = true;

                // Update existing embedded document
                } else {
                    $update = $this->prepareUpdateData($new);
                    foreach ($update as $cmd => $values) {
                        foreach ($values as $key => $value) {
                            $updateData[$cmd][$mapping['name'] . '.' . $key] = $value;
                        }
                    }
                }

            // @ReferenceMany, @EmbedMany
            } elseif (isset($mapping['association']) && $mapping['type'] === 'many' && $new) {
                if (CollectionHelper::isAtomic($mapping['strategy']) && $this->uow->isCollectionScheduledForUpdate($new)) {
                    $updateData['$set'][$mapping['name']] = $this->prepareAssociatedCollectionValue($new, true);
                } elseif (CollectionHelper::isAtomic($mapping['strategy']) && $this->uow->isCollectionScheduledForDeletion($new)) {
                    $updateData['$unset'][$mapping['name']] = true;
                    $this->uow->unscheduleCollectionDeletion($new);
                } elseif (CollectionHelper::isAtomic($mapping['strategy']) && $this->uow->isCollectionScheduledForDeletion($old)) {
                    $updateData['$unset'][$mapping['name']] = true;
                    $this->uow->unscheduleCollectionDeletion($old);
                } elseif ($mapping['association'] === ClassMetadata::EMBED_MANY) {
                    foreach ($new as $key => $embeddedDoc) {
                        if ( ! $this->uow->isScheduledForInsert($embeddedDoc)) {
                            $update = $this->prepareUpdateData($embeddedDoc);
                            foreach ($update as $cmd => $values) {
                                foreach ($values as $name => $value) {
                                    $updateData[$cmd][$mapping['name'] . '.' . $key . '.' . $name] = $value;
                                }
                            }
                        }
                    }
                }

            // @ReferenceOne
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_ONE) {
                if (isset($new) || $mapping['nullable'] === true) {
                    $updateData['$set'][$mapping['name']] = (is_null($new) ? null : $this->prepareReferencedDocumentValue($mapping, $new));
                } else {
                    $updateData['$unset'][$mapping['name']] = true;
                }
            }
        }
        // collections that aren't dirty but could be subject to update are
        // excluded from change set, let's go through them now
        foreach ($this->uow->getScheduledCollections($document) as $coll) {
            $mapping = $coll->getMapping();
            if (CollectionHelper::isAtomic($mapping['strategy']) && $this->uow->isCollectionScheduledForUpdate($coll)) {
                $updateData['$set'][$mapping['name']] = $this->prepareAssociatedCollectionValue($coll, true);
            } elseif (CollectionHelper::isAtomic($mapping['strategy']) && $this->uow->isCollectionScheduledForDeletion($coll)) {
                $updateData['$unset'][$mapping['name']] = true;
                $this->uow->unscheduleCollectionDeletion($coll);
            }
            // @ReferenceMany is handled by CollectionPersister
        }
        return $updateData;
    }

    /**
     * Prepares the update query to upsert a given document object in mongodb.
     *
     * @param object $document
     * @return array $updateData
     */
    public function prepareUpsertData($document)
    {
        $class = $this->dm->getClassMetadata(get_class($document));
        $changeset = $this->uow->getDocumentChangeSet($document);

        $updateData = array();
        foreach ($changeset as $fieldName => $change) {
            $mapping = $class->fieldMappings[$fieldName];

            list($old, $new) = $change;

            // Scalar fields
            if ( ! isset($mapping['association'])) {
                if ($new !== null || $mapping['nullable'] === true) {
                    if ($new !== null && empty($mapping['id']) && isset($mapping['strategy']) && $mapping['strategy'] === ClassMetadataInfo::STORAGE_STRATEGY_INCREMENT) {
                        $operator = '$inc';
                        $value = Type::getType($mapping['type'])->convertToDatabaseValue($new - $old);
                    } else {
                        $operator = '$set';
                        $value = $new === null ? null : Type::getType($mapping['type'])->convertToDatabaseValue($new);
                    }

                    $updateData[$operator][$mapping['name']] = $value;
                }

            // @EmbedOne
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::EMBED_ONE) {
                // If we don't have a new value then do nothing on upsert
                // If we have a new embedded document then lets set the whole thing
                if ($new && $this->uow->isScheduledForInsert($new)) {
                    $updateData['$set'][$mapping['name']] = $this->prepareEmbeddedDocumentValue($mapping, $new);
                } elseif ($new) {
                    // Update existing embedded document
                    $update = $this->prepareUpsertData($new);
                    foreach ($update as $cmd => $values) {
                        foreach ($values as $key => $value) {
                            $updateData[$cmd][$mapping['name'] . '.' . $key] = $value;
                        }
                    }
                }

            // @ReferenceOne
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_ONE) {
                if (isset($new) || $mapping['nullable'] === true) {
                    $updateData['$set'][$mapping['name']] = (is_null($new) ? null : $this->prepareReferencedDocumentValue($mapping, $new));
                }

            // @ReferenceMany, @EmbedMany
            } elseif ($mapping['type'] === ClassMetadata::MANY && ! $mapping['isInverseSide']
                    && $new instanceof PersistentCollectionInterface && $new->isDirty()
                    && CollectionHelper::isAtomic($mapping['strategy'])) {
                $updateData['$set'][$mapping['name']] = $this->prepareAssociatedCollectionValue($new, true);
            }
            // @EmbedMany and @ReferenceMany are handled by CollectionPersister
        }

        // add discriminator if the class has one
        if (isset($class->discriminatorField)) {
            $updateData['$set'][$class->discriminatorField] = isset($class->discriminatorValue)
                ? $class->discriminatorValue
                : $class->name;
        }

        return $updateData;
    }

    /**
     * Returns the reference representation to be stored in MongoDB.
     *
     * If the document does not have an identifier and the mapping calls for a
     * simple reference, null may be returned.
     *
     * @param array $referenceMapping
     * @param object $document
     * @return array|null
     */
    public function prepareReferencedDocumentValue(array $referenceMapping, $document)
    {
        return $this->dm->createDBRef($document, $referenceMapping);
    }

    /**
     * Returns the embedded document to be stored in MongoDB.
     *
     * The return value will usually be an associative array with string keys
     * corresponding to field names on the embedded document. An object may be
     * returned if the document is empty, to ensure that a BSON object will be
     * stored in lieu of an array.
     *
     * If $includeNestedCollections is true, nested collections will be included
     * in this prepared value and the option will cascade to all embedded
     * associations. If any nested PersistentCollections (embed or reference)
     * within this value were previously scheduled for deletion or update, they
     * will also be unscheduled.
     *
     * @param array $embeddedMapping
     * @param object $embeddedDocument
     * @param boolean $includeNestedCollections
     * @return array|object
     * @throws \UnexpectedValueException if an unsupported associating mapping is found
     */
    public function prepareEmbeddedDocumentValue(array $embeddedMapping, $embeddedDocument, $includeNestedCollections = false)
    {
        $embeddedDocumentValue = array();
        $class = $this->dm->getClassMetadata(get_class($embeddedDocument));

        foreach ($class->fieldMappings as $mapping) {
            // Skip notSaved fields
            if ( ! empty($mapping['notSaved'])) {
                continue;
            }

            // Inline ClassMetadataInfo::getFieldValue()
            $rawValue = $class->reflFields[$mapping['fieldName']]->getValue($embeddedDocument);

            $value = null;

            if ($rawValue !== null) {
                switch (isset($mapping['association']) ? $mapping['association'] : null) {
                    // @Field, @String, @Date, etc.
                    case null:
                        $value = Type::getType($mapping['type'])->convertToDatabaseValue($rawValue);
                        break;

                    case ClassMetadata::EMBED_ONE:
                    case ClassMetadata::REFERENCE_ONE:
                        // Nested collections should only be included for embedded relationships
                        $value = $this->prepareAssociatedDocumentValue($mapping, $rawValue, $includeNestedCollections && isset($mapping['embedded']));
                        break;

                    case ClassMetadata::EMBED_MANY:
                    case ClassMetadata::REFERENCE_MANY:
                        // Skip PersistentCollections already scheduled for deletion
                        if ( ! $includeNestedCollections && $rawValue instanceof PersistentCollectionInterface
                            && $this->uow->isCollectionScheduledForDeletion($rawValue)) {
                            break;
                        }

                        $value = $this->prepareAssociatedCollectionValue($rawValue, $includeNestedCollections);
                        break;

                    default:
                        throw new \UnexpectedValueException('Unsupported mapping association: ' . $mapping['association']);
                }
            }

            // Omit non-nullable fields that would have a null value
            if ($value === null && $mapping['nullable'] === false) {
                continue;
            }

            $embeddedDocumentValue[$mapping['name']] = $value;
        }

        /* Add a discriminator value if the embedded document is not mapped
         * explicitly to a targetDocument class.
         */
        if ( ! isset($embeddedMapping['targetDocument'])) {
            $discriminatorField = $embeddedMapping['discriminatorField'];
            $discriminatorValue = isset($embeddedMapping['discriminatorMap'])
                ? array_search($class->name, $embeddedMapping['discriminatorMap'])
                : $class->name;

            /* If the discriminator value was not found in the map, use the full
             * class name. In the future, it may be preferable to throw an
             * exception here (perhaps based on some strictness option).
             *
             * @see DocumentManager::createDBRef()
             */
            if ($discriminatorValue === false) {
                $discriminatorValue = $class->name;
            }

            $embeddedDocumentValue[$discriminatorField] = $discriminatorValue;
        }

        /* If the class has a discriminator (field and value), use it. A child
         * class that is not defined in the discriminator map may only have a
         * discriminator field and no value, so default to the full class name.
         */
        if (isset($class->discriminatorField)) {
            $embeddedDocumentValue[$class->discriminatorField] = isset($class->discriminatorValue)
                ? $class->discriminatorValue
                : $class->name;
        }

        // Ensure empty embedded documents are stored as BSON objects
        if (empty($embeddedDocumentValue)) {
            return (object) $embeddedDocumentValue;
        }

        /* @todo Consider always casting the return value to an object, or
         * building $embeddedDocumentValue as an object instead of an array, to
         * handle the edge case where all database field names are sequential,
         * numeric keys.
         */
        return $embeddedDocumentValue;
    }

    /*
     * Returns the embedded document or reference representation to be stored.
     *
     * @param array $mapping
     * @param object $document
     * @param boolean $includeNestedCollections
     * @return array|object|null
     * @throws \InvalidArgumentException if the mapping is neither embedded nor reference
     */
    public function prepareAssociatedDocumentValue(array $mapping, $document, $includeNestedCollections = false)
    {
        if (isset($mapping['embedded'])) {
            return $this->prepareEmbeddedDocumentValue($mapping, $document, $includeNestedCollections);
        }

        if (isset($mapping['reference'])) {
            return $this->prepareReferencedDocumentValue($mapping, $document);
        }

        throw new \InvalidArgumentException('Mapping is neither embedded nor reference.');
    }

    /**
     * Returns the collection representation to be stored and unschedules it afterwards.
     *
     * @param PersistentCollectionInterface $coll
     * @param bool $includeNestedCollections
     * @return array
     */
    public function prepareAssociatedCollectionValue(PersistentCollectionInterface $coll, $includeNestedCollections = false)
    {
        $mapping = $coll->getMapping();
        $pb = $this;
        $callback = isset($mapping['embedded'])
            ? function($v) use ($pb, $mapping, $includeNestedCollections) {
                return $pb->prepareEmbeddedDocumentValue($mapping, $v, $includeNestedCollections);
            }
            : function($v) use ($pb, $mapping) { return $pb->prepareReferencedDocumentValue($mapping, $v); };

        $setData = $coll->map($callback)->toArray();
        if (CollectionHelper::isList($mapping['strategy'])) {
            $setData = array_values($setData);
        }

        $this->uow->unscheduleCollectionDeletion($coll);
        $this->uow->unscheduleCollectionUpdate($coll);

        return $setData;
    }
}
