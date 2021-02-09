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
     * @template T of object
     * @psalm-param class-string<T> $documentName
     * @psalm-return ObjectRepository<T>
     */
    public function getRepository(DocumentManager $documentManager, string $documentName): ObjectRepository;
}
