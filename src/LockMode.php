<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB;

/**
 * Contains all MongoDB ODM LockModes
 */
final class LockMode
{
    public const int NONE              = 0;
    public const int OPTIMISTIC        = 1;
    public const int PESSIMISTIC_READ  = 2;
    public const int PESSIMISTIC_WRITE = 4;

    private function __construct()
    {
    }
}
