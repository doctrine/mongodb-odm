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
     * @param object $document  The document object to hydrate the data into.
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

            $value = null;

            // Hydrate embedded
            if (isset($mapping['embedded'])) {
                $value = $this->_hydrateEmbedded($mapping, $rawValue);

            // Hydrate reference
            } elseif (isset($mapping['reference'])) {
                $value = $this->_hydrateReference($mapping, $rawValue);

            // Hydrate regular field
            } else {
                $value = $this->_hydrateField($mapping, $rawValue);
            }

            // Set hydrated field value to document
            if ($value !== null) {
                $metadata->setFieldValue($document, $mapping['fieldName'], $value);
            }
        }
        // Set the document identifier
        if (isset($data['_id'])) {
            $metadata->setIdentifierValue($document, $data['_id']);
        }
        return $document;
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

    private function _getClassNameFromDiscriminatorValue(array $mapping, $value)
    {
        $discriminatorField = isset($mapping['discriminatorField']) ? $mapping['discriminatorField'] : '_doctrine_class_name';
        if (isset($value[$discriminatorField])) {
            $discriminatorValue = $value[$discriminatorField];
            return isset($mapping['discriminatorMap'][$discriminatorValue]) ? $mapping['discriminatorMap'][$discriminatorValue] : $discriminatorValue;
        } else {
            return $mapping['targetDocument'];
        }
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

    private function _hydrateReference(array $mapping, array $reference)
    {
        if ($mapping['type'] === 'one' && isset($reference[$this->_cmd . 'id'])) {
            return $this->_hydrateOneReference($mapping, $reference);
        } elseif ($mapping['type'] === 'many' && (is_array($reference) || $reference instanceof Collection)) {
            return $this->_hydrateManyReference($mapping, $reference);
        }
    }

    private function _hydrateOneReference(array $mapping, array $reference)
    {
        $className = $this->_getClassNameFromDiscriminatorValue($mapping, $reference);
        $targetMetadata = $this->_dm->getClassMetadata($className);
        $id = $targetMetadata->getPHPIdentifierValue($reference[$this->_cmd . 'id']);
        return $this->_dm->getReference($className, $id);
    }

    private function _hydrateManyReference(array $mapping, array $references)
    {
        $documents = new PersistentCollection($this->_dm, new ArrayCollection());
        $documents->setInitialized(false);
        foreach ($references as $reference) {
            $document = $this->_hydrateOneReference($mapping, $reference);
            $documents->add($document);
        }
        return $documents;
    }

    private function _hydrateEmbedded(array $mapping, array $embeddedDocument)
    {
        if ($mapping['type'] === 'one') {
            return $this->_hydrateOneEmbedded($mapping, $embeddedDocument);
        } elseif ($mapping['type'] === 'many') {
            return $this->_hydrateManyEmbedded($mapping, $embeddedDocument);
        }
    }

    private function _hydrateOneEmbedded(array $mapping, array $embeddedDocument)
    {
        $className = $this->_getClassNameFromDiscriminatorValue($mapping, $embeddedDocument);
        $embeddedMetadata = $this->_dm->getClassMetadata($className);
        $document = $embeddedMetadata->newInstance();
        return $this->hydrate($embeddedMetadata, $document, $embeddedDocument);
    }

    private function _hydrateManyEmbedded(array $mapping, array $embeddedDocuments)
    {
        $documents = new ArrayCollection();
        foreach ($embeddedDocuments as $embeddedDocument) {
            $document = $this->_hydrateOneEmbedded($mapping, $embeddedDocument);
            $documents->add($document);
        }
        return $documents;
    }

    private function _hydrateField(array $mapping, $value)
    {
        return Type::getType($mapping['type'])->convertToPHPValue($value);
    }
}