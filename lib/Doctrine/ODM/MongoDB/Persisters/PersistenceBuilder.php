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
use Doctrine\ODM\MongoDB\PersistentCollection;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\UnitOfWork;

/**
 * PersistenceBuilder builds the queries used by the persisters to update and insert
 * documents when a DocumentManager is flushed. It uses the changeset information in the
 * UnitOfWork to build queries using atomic operators like $set, $unset, etc.
 *
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
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

            // many collections are inserted later
            if ($mapping['type'] === ClassMetadata::MANY) {
                continue;
            }

            $new = isset($changeset[$mapping['fieldName']][1]) ? $changeset[$mapping['fieldName']][1] : null;

            // Don't store null values unless nullable === true
            if ($new === null && $mapping['nullable'] === false) {
                continue;
            }

            $value = null;
            if ($new !== null) {
                // @Field, @String, @Date, etc.
                if ( ! isset($mapping['association'])) {
                    $value = Type::getType($mapping['type'])->convertToDatabaseValue($new);

                // @ReferenceOne
                } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_ONE) {
                    if ($mapping['isInverseSide']) {
                        continue;
                    }

                    $value = $this->prepareReferencedDocumentValue($mapping, $new);

                // @EmbedOne
                } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::EMBED_ONE) {
                    $value = $this->prepareEmbeddedDocumentValue($mapping, $new);

                // @ReferenceMany
                } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_MANY) {
                    $value = array();
                    foreach ($new as $reference) {
                        $value[] = $this->prepareReferencedDocumentValue($mapping, $reference);
                    }

                // @EmbedMany
                } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::EMBED_MANY) {
                    $value = array();
                    foreach ($new as $reference) {
                        $value[] = $this->prepareEmbeddedDocumentValue($mapping, $reference);
                    }
                }
            }

            $insertData[$mapping['name']] = $value;
        }

        // add discriminator if the class has one
        if ($class->hasDiscriminator()) {
            $insertData[$class->discriminatorField] = $class->discriminatorValue;
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

            // @Inc
            if ($mapping['type'] === 'increment') {
                if ($new === null) {
                    if ($mapping['nullable'] === true) {
                        $updateData['$set'][$mapping['name']] = null;
                    } else {
                        $updateData['$unset'][$mapping['name']] = true;
                    }
                } elseif ($new >= $old) {
                    $updateData['$inc'][$mapping['name']] = $new - $old;
                } else {
                    $updateData['$inc'][$mapping['name']] = ($old - $new) * -1;
                }

            // @Field, @String, @Date, etc.
            } elseif ( ! isset($mapping['association'])) {
                if (isset($new) || $mapping['nullable'] === true) {
                    $updateData['$set'][$mapping['name']] = (is_null($new) ? null : Type::getType($mapping['type'])->convertToDatabaseValue($new));
                } else {
                    $updateData['$unset'][$mapping['name']] = true;
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

            // @EmbedMany
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::EMBED_MANY) {
                if (null !== $new) {
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
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_ONE && $mapping['isOwningSide']) {
                if (isset($new) || $mapping['nullable'] === true) {
                    $updateData['$set'][$mapping['name']] = (is_null($new) ? null : $this->prepareReferencedDocumentValue($mapping, $new));
                } else {
                    $updateData['$unset'][$mapping['name']] = true;
                }

            // @ReferenceMany
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_MANY) {
                // Do nothing right now
            }
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

            // @Inc
            if ($mapping['type'] === 'increment') {
                if ($new >= $old) {
                    $updateData['$inc'][$mapping['name']] = $new - $old;
                } else {
                    $updateData['$inc'][$mapping['name']] = ($old - $new) * -1;
                }

            // @Field, @String, @Date, etc.
            } elseif ( ! isset($mapping['association'])) {
                if (isset($new) || $mapping['nullable'] === true) {
                    $updateData['$set'][$mapping['name']] = (is_null($new) ? null : Type::getType($mapping['type'])->convertToDatabaseValue($new));
                }

            // @EmbedOne
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::EMBED_ONE) {
                // If we have a new embedded document then lets set the whole thing
                if ($new && $this->uow->isScheduledForInsert($new)) {
                    $updateData['$set'][$mapping['name']] = $this->prepareEmbeddedDocumentValue($mapping, $new);

                // If we don't have a new value then do nothing on upsert
                } elseif ( ! $new) {

                // Update existing embedded document
                } else {
                    $update = $this->prepareUpsertData($new);
                    foreach ($update as $cmd => $values) {
                        foreach ($values as $key => $value) {
                            $updateData[$cmd][$mapping['name'] . '.' . $key] = $value;
                        }
                    }
                }

            // @EmbedMany
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::EMBED_MANY && $new) {
                foreach ($new as $key => $embeddedDoc) {
                    if ( ! $this->uow->isScheduledForInsert($embeddedDoc)) {
                        $update = $this->prepareUpsertData($embeddedDoc);
                        foreach ($update as $cmd => $values) {
                            foreach ($values as $name => $value) {
                                $updateData[$cmd][$mapping['name'] . '.' . $key . '.' . $name] = $value;
                            }
                        }
                    }
                }

            // @ReferenceOne
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_ONE && $mapping['isOwningSide']) {
                if (isset($new) || $mapping['nullable'] === true) {
                    $updateData['$set'][$mapping['name']] = (is_null($new) ? null : $this->prepareReferencedDocumentValue($mapping, $new));
                }

            // @ReferenceMany
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_MANY) {
                // Do nothing right now
            }
        }

        // add discriminator if the class has one
        if ($class->hasDiscriminator()) {
            $updateData['$set'][$class->discriminatorField] = $class->discriminatorValue;
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
     * @param array $embeddedMapping
     * @param object $embeddedDocument
     * @return array|object
     */
    public function prepareEmbeddedDocumentValue(array $embeddedMapping, $embeddedDocument)
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
                        $value = $this->prepareAssociatedDocumentValue($mapping, $rawValue);
                        break;

                    case ClassMetadata::EMBED_MANY:
                    case ClassMetadata::REFERENCE_MANY:
                        // Skip PersistentCollections already scheduled for deletion/update
                        if ($rawValue instanceof PersistentCollection &&
                            ($this->uow->isCollectionScheduledForDeletion($rawValue) ||
                             $this->uow->isCollectionScheduledForUpdate($rawValue))) {
                            break;
                        }

                        $pb = $this;
                        $value = $rawValue->map(function($v) use ($pb, $mapping) {
                            return $pb->prepareAssociatedDocumentValue($mapping, $v);
                        })->toArray();

                        // Numerical reindexing may be necessary to ensure BSON array storage
                        if (in_array($mapping['strategy'], array('setArray', 'pushAll', 'addToSet'))) {
                            $value = array_values($value);
                        }
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

        if ($class->hasDiscriminator()) {
            $embeddedDocumentValue[$class->discriminatorField] = $class->discriminatorValue;
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
     * @return array|object|null
     */
    public function prepareAssociatedDocumentValue(array $mapping, $document)
    {
        if (isset($mapping['embedded'])) {
            return $this->prepareEmbeddedDocumentValue($mapping, $document);
        }

        if (isset($mapping['reference'])) {
            return $this->prepareReferencedDocumentValue($mapping, $document);
        }

        throw new \InvalidArgumentException('Mapping is neither embedded nor reference.');
    }

    /**
     * @param object $document
     * @return boolean
     */
    private function isScheduledForInsert($document)
    {
        return $this->uow->isScheduledForInsert($document)
            || $this->uow->getDocumentPersister(get_class($document))->isQueuedForInsert($document);
    }
}
