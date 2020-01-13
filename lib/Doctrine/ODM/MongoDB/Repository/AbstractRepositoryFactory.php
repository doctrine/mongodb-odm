<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectRepository;
use function ltrim;
use function spl_object_hash;

/**
 * Abstract factory for creating document repositories.
 */
abstract class AbstractRepositoryFactory implements RepositoryFactory
{
    /**
     * The list of DocumentRepository instances.
     *
     * @var ObjectRepository[]
     */
    private $repositoryList = [];

    /**
     * {@inheritdoc}
     */
    public function getRepository(DocumentManager $documentManager, string $documentName) : ObjectRepository
    {
        $metadata = $documentManager->getClassMetadata($documentName);
        $hashKey  = $metadata->getName() . spl_object_hash($documentManager);

        if (isset($this->repositoryList[$hashKey])) {
            return $this->repositoryList[$hashKey];
        }

        $repository = $this->createRepository($documentManager, ltrim($documentName, '\\'));

        $this->repositoryList[$hashKey] = $repository;

        return $repository;
    }

    /**
     * Create a new repository instance for a document class.
     *
     * @return ObjectRepository|GridFSRepository
     */
    protected function createRepository(DocumentManager $documentManager, string $documentName) : ObjectRepository
    {
        $metadata = $documentManager->getClassMetadata($documentName);

        if ($metadata->customRepositoryClassName) {
            $repositoryClassName = $metadata->customRepositoryClassName;
        } elseif ($metadata->isFile) {
            $repositoryClassName = $documentManager->getConfiguration()->getDefaultGridFSRepositoryClassName();
        } else {
            $repositoryClassName = $documentManager->getConfiguration()->getDefaultDocumentRepositoryClassName();
        }

        return $this->instantiateRepository($repositoryClassName, $documentManager, $metadata);
    }

    /**
     * Instantiates requested repository.
     */
    abstract protected function instantiateRepository(string $repositoryClassName, DocumentManager $documentManager, ClassMetadata $metadata) : ObjectRepository;
}
