<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Interface for document repository factory.
 */
interface RepositoryFactory
{
    /**
     * Gets the repository for a document class.
     */
    public function getRepository(DocumentManager $documentManager, string $documentName): ObjectRepository;
}
