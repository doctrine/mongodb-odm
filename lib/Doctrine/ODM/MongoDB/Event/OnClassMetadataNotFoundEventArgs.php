<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Class that holds event arguments for a `onClassMetadataNotFound` event.
 *
 * This object is mutable by design, allowing callbacks having access to it to set the
 * found metadata in it, and therefore "cancelling" a `onClassMetadataNotFound` event
 */
final class OnClassMetadataNotFoundEventArgs extends ManagerEventArgs
{
    /**
     * @var string
     * @psalm-var class-string
     */
    private $className;

    /** @var ClassMetadata<object>|null */
    private $foundMetadata;

    /**
     * @psalm-param class-string $className
     */
    public function __construct(string $className, DocumentManager $dm)
    {
        $this->className = $className;

        parent::__construct($dm);
    }

    /**
     * @param ClassMetadata<object>|null $classMetadata
     */
    public function setFoundMetadata(?ClassMetadata $classMetadata = null): void
    {
        $this->foundMetadata = $classMetadata;
    }

    /**
     * @return ClassMetadata<object>|null
     */
    public function getFoundMetadata(): ?ClassMetadata
    {
        return $this->foundMetadata;
    }

    /**
     * Retrieve class name for which a failed metadata fetch attempt was executed
     *
     * @psalm-return class-string
     */
    public function getClassName(): string
    {
        return $this->className;
    }
}
