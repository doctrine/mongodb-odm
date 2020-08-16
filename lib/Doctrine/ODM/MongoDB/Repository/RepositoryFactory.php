<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentManagerInterface;
use Doctrine\Persistence\ObjectRepository;

/**
 * Interface for document repository factory.
 */
interface RepositoryFactory
{
    /**
     * Gets the repository for a document class.
     */
    public function getRepository(DocumentManagerInterface $documentManager, string $documentName) : ObjectRepository;
}
