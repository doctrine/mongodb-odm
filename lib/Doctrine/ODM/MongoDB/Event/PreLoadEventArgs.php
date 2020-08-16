<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManagerInterface;

/**
 * Class that holds event arguments for a preLoad event.
 */
final class PreLoadEventArgs extends LifecycleEventArgs
{
    /** @var array */
    private $data;

    public function __construct(object $document, DocumentManagerInterface $dm, array &$data)
    {
        parent::__construct($document, $dm);
        $this->data =& $data;
    }

    /**
     * Get the array of data to be loaded and hydrated.
     */
    public function &getData() : array
    {
        return $this->data;
    }
}
