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

use Doctrine\ODM\MongoDB\Query,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\PersistentCollection,
    Doctrine\ODM\MongoDB\Mapping\Types\Type,
    Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\Collections\Collection;

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
     * The DocumentManager associationed with this Hydrator
     *
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    private $_dm;

    /**
     * Mongo command prefix
     * @var string
     */
    private $_cmd;

    /**
     * Create a new Hydrator instance
     *
     * @param Doctrine\ODM\MongoDB\DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->_dm = $dm;
        $this->_cmd = $dm->getConfiguration()->getMongoCmd();
    }

    /**
     * Hydrate array of MongoDB document data into the given document object
     * based on the mapping information provided in the ClassMetadata instance.
     *
     * @param ClassMetadata $metadata  The ClassMetadata instance for mapping information.
     * @param string $document  The document object to hydrate the data into.
     * @param array $data The array of document data.
     * @return array $values The array of hydrated values.
     */
    public function hydrate(ClassMetadata $metadata, $document, $data)
    {
        foreach ($metadata->fieldMappings as $mapping) {
            $this->_executeAlsoLoadMethods($document, $mapping, $data);

            $rawValue = $this->_getFieldValue($mapping, $document, $data);
            if ($rawValue === null) {
                continue;
            }

            // Hydrate embedded
            if (isset($mapping['embedded'])) {
                $this->_hydrateEmbedded($metadata, $document, $mapping, $rawValue);

            // Hydrate reference
            } elseif (isset($mapping['reference'])) {
                $this->_hydrateReference($metadata, $document, $mapping, $rawValue);

            // Hydrate regular field
            } else {
                $this->_hydrateField($metadata, $document, $mapping, $rawValue);
            }
        }
        // Set the document identifier
        if (isset($data['_id'])) {
            $metadata->setIdentifierValue($document, $data['_id']);
        }
    }

    private function _getFieldValue(array $mapping, $document, $data)
    {
        $names = isset($mapping['alsoLoadFields']) ? $mapping['alsoLoadFields'] : array();
        array_unshift($names, $mapping['fieldName']);
        foreach ($names as $name) {
            if (isset($data[$name])) {
                return $data[$name];
            }
        }
        return null;
    }

    private function _executeAlsoLoadMethods($document, array $mapping, array $data)
    {
        if (isset($mapping['alsoLoadMethods'])) {
            foreach ($mapping['alsoLoadMethods'] as $method) {
                if (isset($data[$mapping['fieldName']])) {
                    $document->$method($data[$mapping['fieldName']]);
                }
            }
        }
    }

    private function _hydrateReference(ClassMetadata $metadata, $document, array $mapping, $rawValue)
    {
        if ($mapping['type'] === 'one' && isset($rawValue[$this->_cmd . 'id'])) {
            $this->_hydrateOneReference($metadata, $document, $mapping, $rawValue);
        } elseif ($mapping['type'] === 'many' && (is_array($rawValue) || $rawValue instanceof Collection)) {
            $this->_hydrateManyReference($metadata, $document, $mapping, $rawValue);
        }
    }

    private function _hydrateOneReference(ClassMetadata $metadata, $document, array $mapping, $rawValue)
    {
        $className = isset($rawValue['_doctrine_class_name']) ? $rawValue['_doctrine_class_name'] : $mapping['targetDocument'];
        $targetMetadata = $this->_dm->getClassMetadata($className);
        $targetDocument = $targetMetadata->newInstance();
        $id = $targetMetadata->getPHPIdentifierValue($rawValue[$this->_cmd . 'id']);
        $proxy = $this->_dm->getReference($className, $id);
        $metadata->setFieldValue($document, $mapping['fieldName'], $proxy);
    }

    private function _hydrateManyReference(ClassMetadata $metadata, $document, array $mapping, $rawValue)
    {
        $documents = new PersistentCollection($this->_dm, new ArrayCollection());
        $documents->setInitialized(false);
        foreach ($rawValue as $v) {
            $className = isset($v['_doctrine_class_name']) ? $v['_doctrine_class_name'] : $mapping['targetDocument'];
            $targetMetadata = $this->_dm->getClassMetadata($className);
            $targetDocument = $targetMetadata->newInstance();
            $id = $targetMetadata->getPHPIdentifierValue($v[$this->_cmd . 'id']);
            $proxy = $this->_dm->getReference($className, $id);
            $documents->add($proxy);
        }
        $metadata->setFieldValue($document, $mapping['fieldName'], $documents);
    }

    private function _hydrateEmbedded(ClassMetadata $metadata, $document, array $mapping, $rawValue)
    {
        if ($mapping['type'] === 'one') {
            $this->_hydrateOneEmbedded($metadata, $document, $mapping, $rawValue);
        } elseif ($mapping['type'] === 'many') {
            $this->_hydrateManyEmbedded($metadata, $document, $mapping, $rawValue);
        }
    }

    private function _hydrateOneEmbedded(ClassMetadata $metadata, $document, array $mapping, $rawValue)
    {
        $className = isset($rawValue['_doctrine_class_name']) ? $rawValue['_doctrine_class_name'] : $mapping['targetDocument'];
        $embeddedMetadata = $this->_dm->getClassMetadata($className);
        $value = $embeddedMetadata->newInstance();
        $this->hydrate($embeddedMetadata, $value, $rawValue);
        $metadata->setFieldValue($document, $mapping['fieldName'], $value);
        return $value;
    }

    private function _hydrateManyEmbedded(ClassMetadata $metadata, $document, array $mapping, $rawValue)
    {
        $documents = new ArrayCollection();
        foreach ($rawValue as $docArray) {
            $className = isset($docArray['_doctrine_class_name']) ? $docArray['_doctrine_class_name'] : $mapping['targetDocument'];
            $embeddedMetadata = $this->_dm->getClassMetadata($className);
            $doc = $embeddedMetadata->newInstance();
            $this->hydrate($embeddedMetadata, $doc, $docArray);
            $documents->add($doc);
        }
        $metadata->setFieldValue($document, $mapping['fieldName'], $documents);
    }

    private function _hydrateField(ClassMetadata $metadata, $document, array $mapping, $rawValue)
    {
        $value = Type::getType($mapping['type'])->convertToPHPValue($rawValue);
        $metadata->setFieldValue($document, $mapping['fieldName'], $value);
    }
}