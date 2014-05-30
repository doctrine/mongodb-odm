<?php

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * This factory is used to create default repository objects for entities at runtime.
 */
class DefaultRepositoryFactory implements RepositoryFactory
{
    /**
     * The list of DocumentRepository instances.
     *
     * @var array<\Doctrine\Common\Persistence\ObjectRepository>
     */
    private $repositoryList = array();

    /**
     * {@inheritdoc}
     */
    public function getRepository(DocumentManager $documentManager, $documentName)
    {
        $documentName = ltrim($documentName, '\\');

        if (isset($this->repositoryList[$documentName])) {
            return $this->repositoryList[$documentName];
        }

        $repository = $this->createRepository($documentManager, $documentName);

        $this->repositoryList[$documentName] = $repository;

        return $repository;
    }

    /**
     * Create a new repository instance for a document class.
     *
     * @param DocumentManager $documentManager The DocumentManager instance.
     * @param string          $documentName    The name of the document.
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function createRepository(DocumentManager $documentManager, $documentName)
    {
        $metadata            = $documentManager->getClassMetadata($documentName);
        $repositoryClassName = $metadata->customRepositoryClassName;

        if ($repositoryClassName === null) {
            $configuration       = $documentManager->getConfiguration();
            $repositoryClassName = $configuration->getDefaultRepositoryClassName();
        }

        return new $repositoryClassName($documentManager, $documentManager->getUnitOfWork(), $metadata);
    }
}