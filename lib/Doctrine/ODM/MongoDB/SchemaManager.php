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

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;
use InvalidArgumentException;

class SchemaManager
{
    /**
     * @var Doctrine\ODM\MongoDB\DocumentManager
     */
    protected $dm;

    /**
     *
     * @var Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory
     */
    protected $metadataFactory;

    /**
     * @param Doctrine\ODM\MongoDB\DocumentManager $dm
     */
    public function __construct(DocumentManager $dm, ClassMetadataFactory $cmf)
    {
        $this->dm = $dm;
        $this->metadataFactory = $cmf;
    }

    /**
     * Ensure indexes are created for all documents that can be loaded with the
     * metadata factory.
     */
    public function ensureIndexes()
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
                continue;
            }
            $this->ensureDocumentIndexes($class->name);
        }
    }

    public function  getDocumentIndexes($documentName)
    {
        $visited = array();
        return $this->doGetDocumentIndexes($documentName, $visited);
    }

    private function doGetDocumentIndexes($documentName, array &$visited)
    {
        if (isset($visited[$documentName])) {
            return array();
        }

        $visited[$documentName] = true;

        $class = $this->dm->getClassMetadata($documentName);
        $indexes = $this->prepareIndexes($class);

        // Add indexes from embedded & referenced documents
        foreach ($class->fieldMappings as $fieldMapping) {
            if (isset($fieldMapping['embedded']) && isset($fieldMapping['targetDocument'])) {
                $embeddedIndexes = $this->doGetDocumentIndexes($fieldMapping['targetDocument'], $visited);
                foreach ($embeddedIndexes as $embeddedIndex) {
                    foreach ($embeddedIndex['keys'] as $key => $value) {
                        $embeddedIndex['keys'][$fieldMapping['name'] . '.' . $key] = $value;
                        unset($embeddedIndex['keys'][$key]);
                    }
                    $indexes[] = $embeddedIndex;
                }

            } else if (isset($fieldMapping['reference']) && isset($fieldMapping['targetDocument'])) {
                foreach ($indexes as $idx => $index) {
                    $newKeys = array();
                    foreach ($index['keys'] as $key => $v) {
                        if ($key == $fieldMapping['name']) {
                            $key = $key . '.$id';
                        }
                        $newKeys[$key] = $v;
                    }
                    $indexes[$idx]['keys'] = $newKeys;
                }
            }
        }
        return $indexes;
    }

    private function prepareIndexes(ClassMetadata $class)
    {
        $indexes = $class->getIndexes();
        $newIndexes = array();
        $discriminatorFieldName = ($class->discriminatorField !== null) ? $class->discriminatorField['name'] : null;
        foreach ($indexes as $index) {
            $newIndex = array(
                'keys' => array(),
                'options' => $index['options']
            );
            foreach ($index['keys'] as $key => $value) {
                if ($key === $discriminatorFieldName) {
                    //There's no mapping for the discriminator field,
                    //but it's still a valid document field.
                    $newIndex['keys'][$discriminatorFieldName] = $value;
                } elseif ($class->hasField($key)) {
                    $mapping = $class->getFieldMapping($key);
                    $newIndex['keys'][$mapping['name']] = $value;
                } else {
                    $newIndex['keys'][$key] = $value;
                }
            }

            $newIndexes[] = $newIndex;
        }

        return $newIndexes;
    }

    /**
     * Ensure the given documents indexes are created.
     *
     * @param string $documentName The document name to ensure the indexes for.
     */
    public function ensureDocumentIndexes($documentName)
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
            throw new InvalidArgumentException('Cannot create document indexes for mapped super classes or embedded documents.');
        }
        if ($indexes = $this->getDocumentIndexes($documentName)) {
            $collection = $this->dm->getDocumentCollection($class->name);
            foreach ($indexes as $index) {
                $collection->ensureIndex($index['keys'], $index['options']);
            }
        }
    }

    /**
     * Delete indexes for all documents that can be loaded with the
     * metadata factory.
     */
    public function deleteIndexes()
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
                continue;
            }
            $this->deleteDocumentIndexes($class->name);
        }
    }

    /**
     * Delete the given documents indexes.
     *
     * @param string $documentName The document name to delete the indexes for.
     */
    public function deleteDocumentIndexes($documentName)
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
            throw new InvalidArgumentException('Cannot delete document indexes for mapped super classes or embedded documents.');
        }
        $this->dm->getDocumentCollection($documentName)->deleteIndexes();
    }

    /**
     * Create all the mapped document collections in the metadata factory.
     */
    public function createCollections()
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
                continue;
            }
            $this->createDocumentCollection($class->name);
        }
    }

    /**
     * Create the document collection for a mapped class.
     *
     * @param string $documentName
     */
    public function createDocumentCollection($documentName)
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
            throw new InvalidArgumentException('Cannot create document collection for mapped super classes or embedded documents.');
        }
        $this->dm->getDocumentDatabase($documentName)->createCollection(
            $class->getCollection(),
            $class->getCollectionCapped(),
            $class->getCollectionSize(),
            $class->getCollectionMax()
        );
    }

    /**
     * Drop all the mapped document collections in the metadata factory.
     */
    public function dropCollections()
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
                continue;
            }
            $this->dropDocumentCollection($class->name);
        }
    }

    /**
     * Drop the document collection for a mapped class.
     *
     * @param string $documentName
     */
    public function dropDocumentCollection($documentName)
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
            throw new InvalidArgumentException('Cannot delete document indexes for mapped super classes or embedded documents.');
        }
        $this->dm->getDocumentDatabase($documentName)->dropCollection(
            $class->getCollection()
        );
    }

    /**
     * Drop all the mapped document databases in the metadata factory.
     */
    public function dropDatabases()
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
                continue;
            }
            $this->dropDocumentDatabase($class->name);
        }
    }

    /**
     * Drop the document database for a mapped class.
     *
     * @param string $documentName
     */
    public function dropDocumentDatabase($documentName)
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
            throw new InvalidArgumentException('Cannot drop document database for mapped super classes or embedded documents.');
        }
        $this->dm->getDocumentDatabase($documentName)->drop();
    }

    /**
     * Create all the mapped document databases in the metadata factory.
     */
    public function createDatabases()
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
                continue;
            }
            $this->createDocumentDatabase($class->name);
        }
    }

    /**
     * Create the document database for a mapped class.
     *
     * @param string $documentName
     */
    public function createDocumentDatabase($documentName)
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
            throw new InvalidArgumentException('Cannot delete document indexes for mapped super classes or embedded documents.');
        }
        $this->dm->getDocumentDatabase($documentName)->execute("function() { return true; }");
    }
}
