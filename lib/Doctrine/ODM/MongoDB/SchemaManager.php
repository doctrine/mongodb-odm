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
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
        $this->metadataFactory = $dm->getMetadataFactory();
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
        if ($indexes = $class->getIndexes()) {
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
        $this->dm->getDocumentDB($documentName)->createCollection(
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
        $this->dm->getDocumentDB($documentName)->dropCollection(
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
        $this->dm->getDocumentDB($documentName)->drop();
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
        $this->dm->getDocumentDB($documentName)->execute("function() { return true; }");
    }
}