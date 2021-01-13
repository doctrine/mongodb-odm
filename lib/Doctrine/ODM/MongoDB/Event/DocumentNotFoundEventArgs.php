<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Provides event arguments for the documentNotFound event.
 */
final class DocumentNotFoundEventArgs extends LifecycleEventArgs
{
    /** @var mixed */
    private $identifier;

    /** @var bool */
    private $disableException = false;

    /**
     * @param mixed $identifier
     */
    public function __construct(object $document, DocumentManager $dm, $identifier)
    {
        parent::__construct($document, $dm);
        $this->identifier = $identifier;
    }

    /**
     * Retrieve associated identifier.
     *
     * @return mixed
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Indicates whether the proxy initialization exception is disabled.
     */
    public function isExceptionDisabled(): bool
    {
        return $this->disableException;
    }

    /**
     * Disable the throwing of an exception
     *
     * This method indicates to the proxy initializer that the missing document
     * has been handled and no exception should be thrown. This can't be reset.
     */
    public function disableException(bool $disableException = true): void
    {
        $this->disableException = $disableException;
    }
}
