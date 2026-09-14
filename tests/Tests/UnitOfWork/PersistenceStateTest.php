<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\UnitOfWork;

use Doctrine\ODM\MongoDB\UnitOfWork\PersistenceState;
use PHPUnit\Framework\TestCase;

class PersistenceStateTest extends TestCase
{
    public function testCasesAreDistinct(): void
    {
        self::assertCount(4, PersistenceState::cases());
        self::assertNotSame(PersistenceState::New, PersistenceState::Managed);
        self::assertNotSame(PersistenceState::New, PersistenceState::Detached);
        self::assertNotSame(PersistenceState::New, PersistenceState::Removed);
        self::assertNotSame(PersistenceState::Managed, PersistenceState::Detached);
        self::assertNotSame(PersistenceState::Managed, PersistenceState::Removed);
        self::assertNotSame(PersistenceState::Detached, PersistenceState::Removed);
    }
}
