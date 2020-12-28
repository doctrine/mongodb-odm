<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Types;

/**
 * Types implementing this interface can be used for version fields.
 */
interface Versionable
{
    /**
     * Calculates next version.
     *
     * @param mixed $current version currently in use, null if not versioned yet (i.e. first version)
     *
     * @return mixed
     */
    public function getNextVersion($current);
}
