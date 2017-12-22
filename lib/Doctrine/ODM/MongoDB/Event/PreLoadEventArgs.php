<?php

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Class that holds event arguments for a preLoad event.
 *
 * @since 1.0
 */
class PreLoadEventArgs extends LifecycleEventArgs
{
    /**
     * @var array
     */
    private $data;

    /**
     * Constructor.
     *
     * @param object          $document
     * @param DocumentManager $dm
     * @param array           $data     Array of data to be loaded and hydrated
     */
    public function __construct($document, DocumentManager $dm, array &$data)
    {
        parent::__construct($document, $dm);
        $this->data =& $data;
    }

    /**
     * Get the array of data to be loaded and hydrated.
     *
     * @return array
     */
    public function &getData()
    {
        return $this->data;
    }
}
