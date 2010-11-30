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

namespace Doctrine\ODM\MongoDB;

use Doctrine\Common\EventManager;

use Doctrine\ODM\MongoDB\Query,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mapping\Types\Type,
    Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Collections\Collection,
    Doctrine\ODM\MongoDB\Event\LifecycleEventArgs,
    Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;

/**
 * The Hydrator class is responsible for converting a document from MongoDB
 * which is an array to classes and collections based on the mapping of the document
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Hydrator
{
    /**
     * The DocumentManager associated with this Hydrator
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * The EventManager associated with this Hydrator
     *
     * @var Doctrine\Common\EventManager
     */
    private $evm;

    /**
     * Mongo command prefix
     * @var string
     */
    private $cmd;

    /**
     * Create a new Hydrator instance
     *
     * @param Doctrine\ODM\MongoDB\DocumentManager $dm
     * @param Doctrine\Common\EventManager $evm
     * @param string $cmd
     */
    public function __construct(DocumentManager $dm, EventManager $evm, $cmd)
    {
        $this->dm = $dm;
        $this->evm = $evm;
        $this->cmd = $cmd;
    }

    /**
     * Hydrate array of MongoDB document data into the given document object.
     *
     * @param object $document  The document object to hydrate the data into.
     * @param array $data The array of document data.
     * @return array $values The array of hydrated values.
     */
    public function hydrate($document, &$data)
    {
        $metadata = $this->dm->getClassMetadata(get_class($document));

        if (isset($metadata->lifecycleCallbacks[Events::preLoad])) {
            $args = array(&$data);
            $metadata->invokeLifecycleCallbacks(Events::preLoad, $document, $args);
        }
        if ($this->evm->hasListeners(Events::preLoad)) {
            $this->evm->dispatchEvent(Events::preLoad, new PreLoadEventArgs($document, $this->dm, $data));
        }

        if (isset($metadata->alsoLoadMethods)) {
            foreach ($metadata->alsoLoadMethods as $fieldName => $method) {
                if (isset($data[$fieldName])) {
                    $document->$method($data[$fieldName]);
                }
            }
        }
        foreach ($metadata->fieldMappings as $mapping) {
            if (isset($mapping['alsoLoadFields'])) {
                $rawValue = null;
                $names = isset($mapping['alsoLoadFields']) ? $mapping['alsoLoadFields'] : array();
                array_unshift($names, $mapping['name']);
                foreach ($names as $name) {
                    if (isset($data[$name])) {
                        $rawValue = $data[$name];
                        break;
                    }
                }
            } else {
                $rawValue = isset($data[$mapping['name']]) ? $data[$mapping['name']] : null;
            }
            $value = null;

            if (isset($mapping['embedded'])) {
                $uow = $this->dm->getUnitOfWork();
                if ($mapping['type'] === 'one') {
                    if ($rawValue === null) {
                        continue;
                    }
                    $embeddedDocument = $rawValue;
                    $className = $this->dm->getClassNameFromDiscriminatorValue($mapping, $embeddedDocument);
                    $embeddedMetadata = $this->dm->getClassMetadata($className);
                    $value = $embeddedMetadata->newInstance();

                    // unset a potential discriminator map field (unless it's a persisted property)
                    $discriminatorField = isset($mapping['discriminatorField']) ? $mapping['discriminatorField'] : '_doctrine_class_name';
                    if (!isset($embeddedMetadata->fieldMappings[$discriminatorField])) {
                        unset($embeddedDocument[$discriminatorField]);
                    }

                    $this->hydrate($value, $embeddedDocument);
                    $uow->registerManaged($value, null, $embeddedDocument);
                    $uow->setParentAssociation($value, $mapping, $document, $mapping['name']);
                } elseif ($mapping['type'] === 'many') {
                    $embeddedDocuments = $rawValue;
                    $coll = new PersistentCollection(new ArrayCollection(), $this->dm, $this->dm->getConfiguration());
                    if ($embeddedDocuments) {
                        foreach ($embeddedDocuments as $key => $embeddedDocument) {
                            $className = $this->dm->getClassNameFromDiscriminatorValue($mapping, $embeddedDocument);
                            $embeddedMetadata = $this->dm->getClassMetadata($className);
                            $embeddedDocumentObject = $embeddedMetadata->newInstance();

                            // unset a potential discriminator map field (unless it's a persisted property)
                            $discriminatorField = isset($mapping['discriminatorField']) ? $mapping['discriminatorField'] : '_doctrine_class_name';
                            if (!isset($embeddedMetadata->fieldMappings[$discriminatorField])) {
                                unset($embeddedDocument[$discriminatorField]);
                            }

                            $this->hydrate($embeddedDocumentObject, $embeddedDocument);
                            $uow->registerManaged($embeddedDocumentObject, null, $embeddedDocument);
                            $uow->setParentAssociation($embeddedDocumentObject, $mapping, $document, $mapping['name'].'.'.$key);
                            $coll->add($embeddedDocumentObject);
                        }
                    }
                    $coll->setOwner($document, $mapping);
                    $coll->takeSnapshot();
                    $value = $coll;
                }
            // Hydrate reference
            } elseif (isset($mapping['reference'])) {
                $reference = $rawValue;
                if ($mapping['type'] === 'one' && isset($reference[$this->cmd . 'id'])) {
                    if ($reference === null) {
                        continue;
                    }
                    $className = $this->dm->getClassNameFromDiscriminatorValue($mapping, $reference);
                    $targetMetadata = $this->dm->getClassMetadata($className);
                    $id = $targetMetadata->getPHPIdentifierValue($reference[$this->cmd . 'id']);
                    $value = $this->dm->getReference($className, $id);
                } elseif ($mapping['type'] === 'many' && (is_array($reference) || $reference instanceof Collection)) {
                    $references = $reference;
                    $value = new PersistentCollection(new ArrayCollection(), $this->dm, $this->dm->getConfiguration());
                    $value->setInitialized(false);
                    $value->setOwner($document, $mapping);

                    // Delay any hydration of reference objects until the collection is
                    // accessed and initialized for the first ime
                    $value->setReferences($references);
                }
            // Hydrate regular field
            } else {
                $value = Type::getType($mapping['type'])->convertToPHPValue($rawValue);
            }

            unset($data[$mapping['name']]);
            // Set hydrated field value to document
            if ($value !== null) {
                $metadata->setFieldValue($document, $mapping['fieldName'], $value);
                $data[$mapping['fieldName']] = $value;
            }
        }
        // Set the document identifier
        if (isset($data['_id'])) {
            $metadata->setIdentifierValue($document, $data['_id']);
            $data[$metadata->identifier] = Type::getType($metadata->fieldMappings[$metadata->identifier]['type'])->convertToPHPValue($data['_id']);
            unset($data['_id']);
        }

        if (isset($metadata->lifecycleCallbacks[Events::postLoad])) {
            $metadata->invokeLifecycleCallbacks(Events::postLoad, $document);
        }
        if ($this->evm->hasListeners(Events::postLoad)) {
            $this->evm->dispatchEvent(Events::postLoad, new LifecycleEventArgs($document, $this->dm));
        }

        return $document;
    }
}