<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentManagerInterface;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectRepository;

/**
 * This factory is used to create default repository objects for documents at runtime.
 */
final class DefaultRepositoryFactory extends AbstractRepositoryFactory
{
    /**
     * {@inheritdoc}
     */
    protected function instantiateRepository(string $repositoryClassName, DocumentManagerInterface $documentManager, ClassMetadata $metadata) : ObjectRepository
    {
        return new $repositoryClassName($documentManager, $documentManager->getUnitOfWork(), $metadata);
    }
}
