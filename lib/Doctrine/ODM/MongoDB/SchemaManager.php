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

namespace Doctrine\ODM\MongoDB;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory;

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
     *
     * @param integer $timeout Timeout (ms) for acknowledged index creation
     */
    public function ensureIndexes($timeout = null)
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
                continue;
            }
            $this->ensureDocumentIndexes($class->name, $timeout);
        }
    }

    /**
     * Ensure indexes exist for all mapped document classes.
     *
     * Indexes that exist in MongoDB but not the document metadata will be
     * deleted.
     *
     * @param integer $timeout Timeout (ms) for acknowledged index creation
     */
    public function updateIndexes($timeout = null)
    {
        foreach ($this->metadataFactory->getAllMetadata() as $class) {
            if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
                continue;
            }
            $this->updateDocumentIndexes($class->name, $timeout);
        }
    }

    /**
     * Ensure indexes exist for the mapped document class.
     *
     * Indexes that exist in MongoDB but not the document metadata will be
     * deleted.
     *
     * @param string $documentName
     * @param integer $timeout Timeout (ms) for acknowledged index creation
     */
    public function updateDocumentIndexes($documentName, $timeout = null)
    {
        $class = $this->dm->getClassMetadata($documentName);

        if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
            throw new \InvalidArgumentException('Cannot update document indexes for mapped super classes or embedded documents.');
        }

        $documentIndexes = $this->getDocumentIndexes($documentName);
        $collection = $this->dm->getDocumentCollection($documentName);
        $mongoIndexes = $collection->getIndexInfo();

        /* Determine which Mongo indexes should be deleted. Exclude the ID index
         * and those that are equivalent to any in the class metadata.
         */
        $self = $this;
        $mongoIndexes = array_filter($mongoIndexes, function($mongoIndex) use ($documentIndexes, $self) {
            if ('_id_' === $mongoIndex['name']) {
                return false;
            }

            foreach ($documentIndexes as $documentIndex) {
                if ($self->isMongoIndexEquivalentToDocumentIndex($mongoIndex, $documentIndex)) {
                    return false;
                }
            }

            return true;
        });

        // Delete indexes that do not exist in class metadata
        foreach ($mongoIndexes as $mongoIndex) {
            if (isset($mongoIndex['name'])) {
                /* Note: MongoCollection::deleteIndex() cannot delete
                 * custom-named indexes, so use the deleteIndexes command.
                 */
                $collection->getDatabase()->command(array(
                    'deleteIndexes' => $collection->getName(),
                    'index' => $mongoIndex['name'],
                ));
            }
        }

        $this->ensureDocumentIndexes($documentName, $timeout);
    }

    public function getDocumentIndexes($documentName)
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
                            $key = $fieldMapping['simple'] ? $key : $key . '.$id';
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
        $persister = $this->dm->getUnitOfWork()->getDocumentPersister($class->name);
        $indexes = $class->getIndexes();
        $newIndexes = array();

        foreach ($indexes as $index) {
            $newIndex = array(
                'keys' => array(),
                'options' => $index['options']
            );
            foreach ($index['keys'] as $key => $value) {
                $key = $persister->prepareFieldName($key);
                if (isset($class->discriminatorField) && $key === $class->discriminatorField['name']) {
                    // The discriminator field may have its own mapping
                    $newIndex['keys'][$class->discriminatorField['fieldName']] = $value;
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
     * Ensure the given document's indexes are created.
     *
     * @param string $documentName
     * @param integer $timeout Timeout (ms) for acknowledged index creation
     */
    public function ensureDocumentIndexes($documentName, $timeout = null)
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
            throw new \InvalidArgumentException('Cannot create document indexes for mapped super classes or embedded documents.');
        }
        if ($indexes = $this->getDocumentIndexes($documentName)) {
            $collection = $this->dm->getDocumentCollection($class->name);
            foreach ($indexes as $index) {
                // TODO: Use "w" for driver versions >= 1.3.0
                if (!isset($index['options']['safe'])) {
                    $index['options']['safe'] = true;
                }
                if (!isset($index['options']['timeout']) && isset($timeout)) {
                    $index['options']['timeout'] = $timeout;
                }
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
     * Delete the given document's indexes.
     *
     * @param string $documentName
     */
    public function deleteDocumentIndexes($documentName)
    {
        $class = $this->dm->getClassMetadata($documentName);
        if ($class->isMappedSuperclass || $class->isEmbeddedDocument) {
            throw new \InvalidArgumentException('Cannot delete document indexes for mapped super classes or embedded documents.');
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
            throw new \InvalidArgumentException('Cannot create document collection for mapped super classes or embedded documents.');
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
            throw new \InvalidArgumentException('Cannot delete document indexes for mapped super classes or embedded documents.');
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
            throw new \InvalidArgumentException('Cannot drop document database for mapped super classes or embedded documents.');
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
            throw new \InvalidArgumentException('Cannot delete document indexes for mapped super classes or embedded documents.');
        }
        $this->dm->getDocumentDatabase($documentName)->execute("function() { return true; }");
    }

    /**
     * Determine if an index returned by MongoCollection::getIndexInfo() can be
     * considered equivalent to an index in class metadata.
     *
     * Indexes are considered different if:
     *
     *   (a) Key/direction pairs differ or are not in the same order
     *   (b) Sparse or unique options differ
     *   (c) Mongo index is unique without dropDups and mapped index is unique
     *       with dropDups
     *   (d) Geospatial options differ (bits, max, min)
     *
     * Regarding (c), the inverse case is not a reason to delete and
     * recreate the index, since dropDups only affects creation of
     * the unique index. Additionally, the background option is only
     * relevant to index creation and is not considered.
     */
    public function isMongoIndexEquivalentToDocumentIndex($mongoIndex, $documentIndex)
    {
        $documentIndexOptions = $documentIndex['options'];

        if ($mongoIndex['key'] != $documentIndex['keys']) {
            return false;
        }

        if (empty($mongoIndex['sparse']) xor empty($documentIndexOptions['sparse'])) {
            return false;
        }

        if (empty($mongoIndex['unique']) xor empty($documentIndexOptions['unique'])) {
            return false;
        }

        if (!empty($mongoIndex['unique']) && empty($mongoIndex['dropDups']) &&
            !empty($documentIndexOptions['unique']) && !empty($documentIndexOptions['dropDups'])) {

            return false;
        }

        foreach (array('bits', 'max', 'min') as $option) {
            if (isset($mongoIndex[$option]) xor isset($documentIndexOptions[$option])) {
                return false;
            }

            if (isset($mongoIndex[$option]) && isset($documentIndexOptions[$option]) &&
                $mongoIndex[$option] !== $documentIndexOptions[$option]) {

                return false;
            }
        }

        return true;
    }
}
