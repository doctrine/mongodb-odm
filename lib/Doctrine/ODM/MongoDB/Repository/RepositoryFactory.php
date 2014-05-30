<?php

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Interface for document repository factory.
 */
interface RepositoryFactory
{
    /**
     * Gets the repository for a document class.
     *
     * @param DocumentManager $documentManager The DocumentManager instance.
     * @param string          $documentName    The name of the document.
     *
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    public function getRepository(DocumentManager $documentManager, $documentName);
}
