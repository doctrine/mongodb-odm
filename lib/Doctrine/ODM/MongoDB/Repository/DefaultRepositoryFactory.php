<?php

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * This factory is used to create default repository objects for documents at runtime.
 */
final class DefaultRepositoryFactory extends AbstractRepositoryFactory
{
    /**
     * {@inheritdoc}
     */
    protected function instantiateRepository($repositoryClassName, DocumentManager $documentManager, ClassMetadata $metadata)
    {
        return new $repositoryClassName($documentManager, $documentManager->getUnitOfWork(), $metadata);
    }
}
