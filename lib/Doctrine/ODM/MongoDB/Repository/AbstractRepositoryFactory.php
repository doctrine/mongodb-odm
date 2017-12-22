<?php

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Abstract factory for creating document repositories.
 *
 * @since 1.2
 */
abstract class AbstractRepositoryFactory implements RepositoryFactory
{
    /**
     * The list of DocumentRepository instances.
     *
     * @var ObjectRepository[]
     */
    private $repositoryList = array();

    /**
     * {@inheritdoc}
     */
    public function getRepository(DocumentManager $documentManager, $documentName)
    {
        $metadata = $documentManager->getClassMetadata($documentName);
        $hashKey = $metadata->getName() . spl_object_hash($documentManager);

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
     * @param DocumentManager $documentManager The DocumentManager instance.
     * @param string          $documentName    The name of the document.
     *
     * @return ObjectRepository
     */
    protected function createRepository(DocumentManager $documentManager, $documentName)
    {
        $metadata            = $documentManager->getClassMetadata($documentName);
        $repositoryClassName = $metadata->customRepositoryClassName ?: $documentManager->getConfiguration()->getDefaultRepositoryClassName();

        return $this->instantiateRepository($repositoryClassName, $documentManager, $metadata);
    }

    /**
     * Instantiates requested repository.
     *
     * @param string $repositoryClassName
     * @param DocumentManager $documentManager
     * @param ClassMetadata $metadata
     * @return ObjectRepository
     */
    abstract protected function instantiateRepository($repositoryClassName, DocumentManager $documentManager, ClassMetadata $metadata);
}
