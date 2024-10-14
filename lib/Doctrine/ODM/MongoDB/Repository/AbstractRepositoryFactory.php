<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\Persistence\ObjectRepository;

use function is_a;
use function spl_object_hash;

/**
 * Abstract factory for creating document repositories.
 */
abstract class AbstractRepositoryFactory implements RepositoryFactory
{
    /**
     * The list of DocumentRepository instances.
     *
     * @var ObjectRepository<object>[]
     */
    private array $repositoryList = [];

    /**
     * @param class-string<T> $documentName
     *
     * @phpstan-return DocumentRepository<T>|GridFSRepository<T>|ViewRepository<T>
     *
     * @template T of object
     */
    public function getRepository(DocumentManager $documentManager, string $documentName): ObjectRepository
    {
        $metadata = $documentManager->getClassMetadata($documentName);
        $hashKey  = $metadata->getName() . spl_object_hash($documentManager);

        if (isset($this->repositoryList[$hashKey])) {
            return $this->repositoryList[$hashKey];
        }

        $repository = $this->createRepository($documentManager, $documentName);

        $this->repositoryList[$hashKey] = $repository;

        return $repository;
    }

    /**
     * Create a new repository instance for a document class.
     *
     * @param class-string<T> $documentName
     *
     * @return DocumentRepository|GridFSRepository|ViewRepository
     * @phpstan-return DocumentRepository<T>|GridFSRepository<T>|ViewRepository<T>
     *
     * @template T of object
     */
    protected function createRepository(DocumentManager $documentManager, string $documentName): ObjectRepository
    {
        $metadata = $documentManager->getClassMetadata($documentName);

        $repositoryClassName = $metadata->isFile
            ? $documentManager->getConfiguration()->getDefaultGridFSRepositoryClassName()
            : $documentManager->getConfiguration()->getDefaultDocumentRepositoryClassName();

        if ($metadata->customRepositoryClassName) {
            $repositoryClassName = $metadata->customRepositoryClassName;
        }

        switch (true) {
            case $metadata->isFile:
                if (! is_a($repositoryClassName, GridFSRepository::class, true)) {
                    throw MappingException::invalidRepositoryClass($documentName, $repositoryClassName, GridFSRepository::class);
                }

                break;

            case $metadata->isView():
                if (! is_a($repositoryClassName, ViewRepository::class, true)) {
                    throw MappingException::invalidRepositoryClass($documentName, $repositoryClassName, ViewRepository::class);
                }

                break;

            case $metadata->isEmbeddedDocument:
                throw MongoDBException::cannotCreateRepository($documentName);

            case $metadata->isMappedSuperclass:
            default:
                if (! is_a($repositoryClassName, DocumentRepository::class, true)) {
                    throw MappingException::invalidRepositoryClass($documentName, $repositoryClassName, DocumentRepository::class);
                }

                break;
        }

        return $this->instantiateRepository($repositoryClassName, $documentManager, $metadata);
    }

    /**
     * Instantiates requested repository.
     *
     * @param ClassMetadata<T> $metadata
     * @param class-string<T>  $repositoryClassName
     *
     * @return ObjectRepository<T>
     *
     * @template T of object
     */
    abstract protected function instantiateRepository(string $repositoryClassName, DocumentManager $documentManager, ClassMetadata $metadata): ObjectRepository;
}
