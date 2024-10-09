<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\Persistence\ObjectRepository;

/**
 * Interface for document repository factory.
 */
interface RepositoryFactory
{
    /**
     * Gets the repository for a document class.
     *
     * @param class-string<T> $documentName
     *
     * @return ObjectRepository<T>
     *
     * @template T of object
     */
    public function getRepository(DocumentManager $documentManager, string $documentName): ObjectRepository;
}
