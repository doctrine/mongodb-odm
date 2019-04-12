<?php

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class that holds event arguments for a `onClassMetadataNotFound` event.
 *
 * This object is mutable by design, allowing callbacks having access to it to set the
 * found metadata in it, and therefore "cancelling" a `onClassMetadataNotFound` event
 */
class OnClassMetadataNotFoundEventArgs extends ManagerEventArgs
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var ClassMetadata|null
     */
    private $foundMetadata;

    /**
     * @param string          $className
     * @param DocumentManager $dm
     */
    public function __construct($className, DocumentManager $dm)
    {
        $this->className = (string) $className;

        parent::__construct($dm);
    }

    /**
     * @param ClassMetadata|null $classMetadata
     */
    public function setFoundMetadata(ClassMetadata $classMetadata = null)
    {
        $this->foundMetadata = $classMetadata;
    }

    /**
     * @return ClassMetadata|null
     */
    public function getFoundMetadata()
    {
        return $this->foundMetadata;
    }

    /**
     * Retrieve class name for which a failed metadata fetch attempt was executed
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}
