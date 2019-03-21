<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

/**
 * Contains all MongoDB ODM LockModes
 */
final class LockMode
{
    public const NONE              = 0;
    public const OPTIMISTIC        = 1;
    public const PESSIMISTIC_READ  = 2;
    public const PESSIMISTIC_WRITE = 4;

    private function __construct()
    {
    }
}
