<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Driver\Session;

/**
 * Class that holds event arguments for a preLoad event.
 */
final class PreLoadEventArgs extends LifecycleEventArgs
{
    /** @var array<string, mixed> */
    private array $data;

    /** @param array<string, mixed> $data */
    public function __construct(
        object $document,
        DocumentManager $dm,
        array &$data,
        ?Session $session = null,
    ) {
        parent::__construct($document, $dm, $session);

        $this->data =& $data;
    }

    /**
     * Get the array of data to be loaded and hydrated.
     *
     * @return array<string, mixed>
     */
    public function &getData(): array
    {
        return $this->data;
    }
}
