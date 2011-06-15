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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */
namespace Doctrine\ODM\MongoDB\Persisters;

use Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\UnitOfWork,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mapping\Types\Type;

/**
 * PersistenceBuilder builds the queries used by the persisters to update and insert
 * documents when a DocumentManager is flushed. It uses the changeset information in the
 * UnitOfWork to build queries using atomic operators like $set, $unset, etc.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class PersistenceBuilder
{
    /**
     * The DocumentManager instance.
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * The UnitOfWork instance.
     *
     * @var Doctrine\ODM\MongoDB\UnitOfWork
     */
    private $uow;

    /**
     * Initializes a new PersistenceBuilder instance.
     *
     * @param Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param Doctrine\ODM\MongoDB\UnitOfWork $uow
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow, $cmd)
    {
        $this->dm = $dm;
        $this->uow = $uow;
        $this->cmd = $cmd;
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
            // skip not saved fields
            if (isset($mapping['notSaved']) && $mapping['notSaved'] === true) {
                continue;
            }
            // Skip version and lock fields
            if (isset($mapping['version']) || isset($mapping['lock'])) {
                continue;
            }

            $new = isset($changeset[$mapping['fieldName']][1]) ? $changeset[$mapping['fieldName']][1] : null;

            // Generate a document identifier
            if ($new === null && $class->identifier === $mapping['fieldName'] && $class->generatorType !== ClassMetadata::GENERATOR_TYPE_NONE) {
                $new = $class->idGenerator->generate($this->dm, $document);
            }

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

                    $oid = spl_object_hash($new);

                    if ($this->isScheduledForInsert($new)) {
                        // The associated document $new is not yet persisted, so we must
                        // set $new = null, in order to insert a null value and schedule an
                        // extra update on the UnitOfWork.
                        $this->uow->scheduleExtraUpdate($document, array(
                            $mapping['fieldName'] => array(null, $new)
                        ));
                    } else {
                        $value = $this->prepareReferencedDocumentValue($mapping, $new);
                    }

                // @EmbedOne
                } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::EMBED_ONE) {
                    $value = $this->prepareEmbeddedDocumentValue($mapping, $new);

                // @ReferenceMany
                } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_MANY) {
                    $value = array();
                    foreach ($new as $reference) {
                        $value[] = $this->prepareReferenceDocValue($mapping, $reference);
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
            $insertData[$class->discriminatorField['name']] = $class->discriminatorValue;
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
        $oid = spl_object_hash($document);
        $class = $this->dm->getClassMetadata(get_class($document));
        $changeset = $this->uow->getDocumentChangeSet($document);

        $updateData = array();
        foreach ($changeset as $fieldName => $change) {
            $mapping = $class->fieldMappings[$fieldName];

            // skip not saved fields
            if (isset($mapping['notSaved']) && $mapping['notSaved'] === true) {
                continue;
            }

            // Skip version and lock fields
            if (isset($mapping['version']) || isset($mapping['lock'])) {
                continue;
            }

            list($old, $new) = $change;

            // @Inc
            if ($mapping['type'] === 'increment') {
                if ($new >= $old) {
                    $updateData[$this->cmd . 'inc'][$mapping['name']] = $new - $old;
                } else {
                    $updateData[$this->cmd . 'inc'][$mapping['name']] = ($old - $new) * -1;
                }

            // @Field, @String, @Date, etc.
            } elseif ( ! isset($mapping['association'])) {
                if (isset($new) || $mapping['nullable'] === true) {
                    $updateData[$this->cmd . 'set'][$mapping['name']] = Type::getType($mapping['type'])->convertToDatabaseValue($new);
                } else {
                    $updateData[$this->cmd . 'unset'][$mapping['name']] = true;
                }

            // @EmbedOne
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::EMBED_ONE) {
                // If we have a new embedded document then lets set the whole thing
                if ($new && $this->uow->isScheduledForInsert($new)) {
                    $updateData[$this->cmd . 'set'][$mapping['name']] = $this->prepareEmbeddedDocumentValue($mapping, $new);

                // If we don't have a new value then lets unset the embedded document
                } elseif ( ! $new) {
                    $updateData[$this->cmd . 'unset'][$mapping['name']] = true;

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

            // @ReferenceOne
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_ONE && $mapping['isOwningSide']) {
                if (isset($new) || $mapping['nullable'] === true) {
                    $updateData[$this->cmd . 'set'][$mapping['name']] = $this->prepareReferencedDocumentValue($mapping, $new);
                } else {
                    $updateData[$this->cmd . 'unset'][$mapping['name']] = true;
                }

            // @ReferenceMany
            } elseif (isset($mapping['association']) && $mapping['association'] === ClassMetadata::REFERENCE_MANY) {
                // Do nothing right now
            }
        }
        return $updateData;
    }

    /**
     * Returns the reference representation to be stored in mongodb or null if not applicable.
     *
     * @param array $referenceMapping
     * @param object $document
     * @return array $referenceDocumentValue
     */
    public function prepareReferencedDocumentValue(array $referenceMapping, $document)
    {
        return $this->dm->createDBRef($document, $referenceMapping);
    }

    /**
     * Prepares array of values to be stored in mongo to represent embedded object.
     *
     * @param array $embeddedMapping
     * @param object $embeddedDocument
     * @return array|object $embeddedDocumentValue
     */
    public function prepareEmbeddedDocumentValue(array $embeddedMapping, $embeddedDocument)
    {
        $className = get_class($embeddedDocument);
        $class = $this->dm->getClassMetadata($className);
        $embeddedDocumentValue = array();
        foreach ($class->fieldMappings as $mapping) {
            // Skip not saved fields
            if (isset($mapping['notSaved']) && $mapping['notSaved'] === true) {
                continue;
            }

            $rawValue = $class->reflFields[$mapping['fieldName']]->getValue($embeddedDocument);

            // Generate a document identifier
            if ($rawValue === null && $class->identifier === $mapping['fieldName'] && $class->generatorType !== ClassMetadata::GENERATOR_TYPE_NONE) {
                $rawValue = $class->idGenerator->generate($this->dm, $embeddedDocument);
                $class->setIdentifierValue($embeddedDocument, $rawValue);
            }

            $value = null;
            if ($rawValue !== null) {
                /** @Field, @String, @Date, etc. */
                if ( ! isset($mapping['association'])) {
                    $value = Type::getType($mapping['type'])->convertToDatabaseValue($rawValue);

                /** @EmbedOne */
                } elseif (isset($mapping['association']) && $mapping['association'] == ClassMetadata::EMBED_ONE) {
                    $value = $this->prepareEmbeddedDocumentValue($mapping, $rawValue);

                /** @EmbedMany */
                } elseif (isset($mapping['association']) && $mapping['association'] == ClassMetadata::EMBED_MANY) {
                    // do nothing for embedded many
                    // CollectionPersister will take care of this

                /** @ReferenceOne */
                } elseif (isset($mapping['association']) && $mapping['association'] == ClassMetadata::REFERENCE_ONE) {
                    $value = $this->prepareReferencedDocumentValue($mapping, $rawValue);
                }
            }

            if ($value === null && $mapping['nullable'] === false) {
                continue;
            }
            $embeddedDocumentValue[$mapping['name']] = $value;
        }

        // Store a discriminator value if the embedded document is not mapped explicitely to a targetDocument
        if ( ! isset($embeddedMapping['targetDocument'])) {
            $discriminatorField = isset($embeddedMapping['discriminatorField']) ? $embeddedMapping['discriminatorField'] : '_doctrine_class_name';
            $discriminatorValue = isset($embeddedMapping['discriminatorMap']) ? array_search($class->getName(), $embeddedMapping['discriminatorMap']) : $class->getName();
            $embeddedDocumentValue[$discriminatorField] = $discriminatorValue;
        }

        // Fix so that we can force empty embedded document to store itself as a hash instead of an array
        if (empty($embeddedDocumentValue)) {
            return (object) $embeddedDocumentValue;
        }

        if ($class->discriminatorField) {
            $embeddedDocumentValue[$class->discriminatorField['name']] = $class->discriminatorValue;
        }

        return $embeddedDocumentValue;
    }

    private function isScheduledForInsert($document)
    {
        return $this->uow->isScheduledForInsert($document)
            || $this->uow->getDocumentPersister(get_class($document))->isQueuedForInsert($document);
    }
}