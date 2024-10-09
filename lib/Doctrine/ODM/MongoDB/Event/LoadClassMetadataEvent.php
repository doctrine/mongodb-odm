<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class that holds event arguments for a loadMetadata event.
 */
final class LoadClassMetadataEvent extends Event
{
    public function __construct(
        private ClassMetadata $classMetadata,
        private DocumentManager $objectManager,
    ) {
    }

    public function getClassMetadata(): ClassMetadata
    {
        return $this->classMetadata;
    }

    public function getDocumentManager(): DocumentManager
    {
        return $this->objectManager;
    }
}
