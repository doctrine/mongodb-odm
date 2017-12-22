<?php

namespace Doctrine\ODM\MongoDB\Repository;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * This factory is used to create default repository objects for entities at runtime.
 *
 * @todo make the default implementation final in 2.0
 * @final since version 1.2
 */
/* final */ class DefaultRepositoryFactory extends AbstractRepositoryFactory
{
    public function __construct()
    {
        if (get_class($this) !== DefaultRepositoryFactory::class) {
            @trigger_error(
                sprintf('The %s class extends %s which will be final in ODM 2.0. You should extend %s instead.', __CLASS__,  DefaultRepositoryFactory::class, AbstractRepositoryFactory::class),
                E_USER_DEPRECATED
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function instantiateRepository($repositoryClassName, DocumentManager $documentManager, ClassMetadata $metadata)
    {
        return new $repositoryClassName($documentManager, $documentManager->getUnitOfWork(), $metadata);
    }
}
