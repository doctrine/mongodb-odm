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
     * Prepares insert data for document
     *
     * @param mixed $document
     * @return array
     */
    public function prepareInsertData($document)
    {
        $oid = spl_object_hash($document);
        $class = $this->dm->getClassMetadata(get_class($document));
        $changeset = $this->uow->getDocumentChangeSet($document);
        $insertData = array();
        foreach ($class->fieldMappings as $mapping) {
            // many collections are inserted later
            if ($mapping['type'] === 'many') {
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

            // Prepare new document identifier
            if ($class->isIdentifier($mapping['fieldName'])) {
                if ( ! $class->isIdGeneratorNone() && $new === null) {
                    $new = $class->idGenerator->generate($this->dm, $document);
                }
                $insertData['_id'] = Type::getType($mapping['type'])->convertToDatabaseValue($new);
                continue;
            }
            // Skip null values
            if ($new === null && $mapping['nullable'] === false) {
                continue;
            }
            $value = $this->prepareValue($mapping, $new);

            // Check if a reference is not persisted yet and we need to schedule an extra update
            $insertData[$mapping['name']] = $value;
            if (isset($mapping['reference'])) {
                $scheduleForUpdate = false;
                if ($mapping['type'] === 'one') {
                    if ( ! isset($insertData[$mapping['name']][$this->cmd . 'id'])) {
                        $scheduleForUpdate = true;
                    }
                }
                if ($scheduleForUpdate) {
                    unset($insertData[$mapping['name']]);
                    $this->uow->scheduleExtraUpdate($document, array(
                        $mapping['fieldName'] => array(null, $new)
                    ));
                }
            }
        }
        // add discriminator if the class has one
        if ($class->hasDiscriminator()) {
            $insertData[$class->discriminatorField['name']] = $class->discriminatorValue;
        }
        return $insertData;
    }

    /**
     * Prepares update array for document, using atomic operators
     *
     * @param mixed $document
     * @return array
     */
    public function prepareUpdateData($document)
    {
        $oid = spl_object_hash($document);
        $class = $this->dm->getClassMetadata(get_class($document));
        $changeset = $this->uow->getDocumentChangeSet($document);
        $result = array();
        foreach ($changeset as $fieldName => $change) {
            $mapping = $class->fieldMappings[$fieldName];
            // many references are persisted with CollectionPersister later
            if (isset($mapping['reference']) && $mapping['type'] === 'many') {
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

            list($old, $new) = $change;

            // Build query to persist updates to an embedded one association
            if (isset($mapping['embedded']) && $mapping['type'] === 'one') {
                // If we have a new embedded document then lets set the whole thing
                if ($new && $this->uow->isScheduledForInsert($new)) {
                    $result[$this->cmd . 'set'][$mapping['name']] = $this->prepareEmbeddedDocValue($mapping, $new);
                // If we don't have a new value then lets unset the embedded document
                } else if ( ! $new) {
                    $result[$this->cmd . 'unset'][$mapping['name']] = true;
                // Update existing embedded document
                } else {
                    $update = $this->prepareUpdateData($new);
                    foreach ($update as $cmd => $values) {
                        foreach ($values as $key => $value) {
                            $result[$cmd][$mapping['name'] . '.' . $key] = $value;
                        }
                    }
                }
            // Build query to persist updates to an embedded many association
            } else if (isset($mapping['embedded']) && $mapping['type'] === 'many') {
                foreach ($new as $key => $embeddedDoc) {
                    if (!$this->uow->isScheduledForInsert($embeddedDoc)) {
                        $update = $this->prepareUpdateData($embeddedDoc);
                        foreach ($update as $cmd => $values) {
                            foreach ($values as $name => $value) {
                                $result[$cmd][$mapping['name'] . '.' . $key . '.' . $name] = $value;
                            }
                        }
                    }
                }
            // Prepare increment type values
            } else if ($mapping['type'] === 'increment') {
                if ($new >= $old) {
                    $result[$this->cmd . 'inc'][$mapping['name']] = $new - $old;
                } else {
                    $result[$this->cmd . 'inc'][$mapping['name']] = ($old - $new) * -1;
                }
            // Persist all other types using $set and $unset
            } else {
                if (isset($new) || $mapping['nullable'] === true) {
                    $new = $this->prepareValue($mapping, $new);
                    $result[$this->cmd . 'set'][$mapping['name']] = $new;
                } else {
                    $result[$this->cmd . 'unset'][$mapping['name']] = true;
                }
            }
        }
        return $result;
    }

    /**
     * Prepare a value based on the given mapping array.
     *
     * @param array $mapping
     * @param mixed $value
     */
    public function prepareValue(array $mapping, $value)
    {
        if ($value === null) {
            return null;
        }
        if ($mapping['type'] === 'many') {
            $prepared = null;
            if ($value) {
                $oneMapping = $mapping;
                $oneMapping['type'] = 'one';
                $prepared = array();
                foreach ($value as $rawValue) {
                    $prepared[] = $this->prepareValue($oneMapping, $rawValue);
                }
            }
        } elseif (isset($mapping['embedded'])) {
            $prepared = $this->prepareEmbeddedDocValue($mapping, $value);
        } elseif (isset($mapping['reference'])) {
            $prepared = $this->prepareReferencedDocValue($mapping, $value);
        } else {
            $prepared = Type::getType($mapping['type'])->convertToDatabaseValue($value);
        }
        return $prepared;
    }

    /**
     * Returns the reference representation to be stored in mongodb or null if not applicable.
     *
     * @param array $referenceMapping
     * @param Document $document
     * @return array|null
     */
    public function prepareReferencedDocValue(array $referenceMapping, $document)
    {
        $id = null;
        if (is_array($document)) {
            $className = $referenceMapping['targetDocument'];
        } else {
            $className = get_class($document);
            $id = $this->uow->getDocumentIdentifier($document);
        }
        $class = $this->dm->getClassMetadata($className);
        if (null !== $id) {
            $id = $class->getDatabaseIdentifierValue($id);
        }
        $ref = array(
            $this->cmd . 'ref' => $class->getCollection(),
            $this->cmd . 'id' => $id,
            $this->cmd . 'db' => $class->getDatabase()
        );

        // Store a discriminator value if the referenced document is not mapped explicitely to a targetDocument
        if ( ! isset($referenceMapping['targetDocument'])) {
            $discriminatorField = isset($referenceMapping['discriminatorField']) ? $referenceMapping['discriminatorField'] : '_doctrine_class_name';
            $discriminatorValue = isset($referenceMapping['discriminatorMap']) ? array_search($class->getName(), $referenceMapping['discriminatorMap']) : $class->getName();
            $ref[$discriminatorField] = $discriminatorValue;
        }
        return $ref;
    }

    /**
     * Prepares array of values to be stored in mongo to represent embedded object.
     *
     * @param array $embeddedMapping
     * @param Document $embeddedDocument
     * @return array
     */
    public function prepareEmbeddedDocValue(array $embeddedMapping, $embeddedDocument)
    {
        $className = get_class($embeddedDocument);
        $class = $this->dm->getClassMetadata($className);
        $embeddedDocumentValue = array();
        foreach ($class->fieldMappings as $mapping) {
            // Skip not saved fields
            if (isset($mapping['notSaved']) && $mapping['notSaved'] === true) {
                continue;
            }

            $rawValue = $class->getFieldValue($embeddedDocument, $mapping['fieldName']);

            // Generate embedded document identifiers
            if ($class->isIdentifier($mapping['fieldName'])) {
                if ( ! $class->isIdGeneratorNone() && $rawValue === null) {
                    $rawValue = $class->idGenerator->generate($this->dm, $embeddedDocument);
                    $class->setIdentifierValue($embeddedDocument, $rawValue);
                }
                $embeddedDocumentValue['_id'] = Type::getType($mapping['type'])->convertToDatabaseValue($rawValue);
                continue;
            }

            // Don't store null values unless nullable is specified
            if ($rawValue === null && $mapping['nullable'] === false) {
                continue;
            }
            $value = null;
            if (isset($mapping['embedded']) && $mapping['type'] == 'one') {
                $value = $this->prepareEmbeddedDocValue($mapping, $rawValue);
            } elseif (isset($mapping['embedded']) && $mapping['type'] == 'many') {
                // do nothing for embedded many
                // CollectionPersister will take care of this
            } elseif (isset($mapping['reference']) && $mapping['type'] === 'one') {
                $value = $this->prepareReferencedDocValue($mapping, $rawValue);
            } else {
                $value = Type::getType($mapping['type'])->convertToDatabaseValue($rawValue);
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
        return $embeddedDocumentValue;
    }
}