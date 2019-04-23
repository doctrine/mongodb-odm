<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Event;

use Doctrine\ODM\MongoDB\DocumentManager;
use const E_USER_DEPRECATED;
use function sprintf;
use function trigger_error;

/**
 * Class that holds event arguments for a preLoad event.
 *
 * @final
 */
class PreLoadEventArgs extends LifecycleEventArgs
{
    /** @var array */
    private $data;

    public function __construct(object $document, DocumentManager $dm, array &$data)
    {
        if (self::class !== static::class) {
            @trigger_error(sprintf('The class "%s" extends "%s" which will be final in MongoDB ODM 2.0.', static::class, self::class), E_USER_DEPRECATED);
        }
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
