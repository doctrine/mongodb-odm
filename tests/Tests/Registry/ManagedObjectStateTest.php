<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Registry;

use Doctrine\ODM\MongoDB\Registry\ManagedObjectState;
use Doctrine\ODM\MongoDB\Registry\ParentAssociation;
use Doctrine\ODM\MongoDB\Registry\PersistenceState;
use PHPUnit\Framework\TestCase;

class ManagedObjectStateTest extends TestCase
{
    public function testInitialState(): void
    {
        $state = new ManagedObjectState(PersistenceState::New);

        self::assertSame(PersistenceState::New, $state->state);
        self::assertNull($state->originalData);
        self::assertNull($state->parentAssociation);
        self::assertNull($state->identifier);
    }

    public function testConstructorAcceptsInitialOriginalData(): void
    {
        $state = new ManagedObjectState(PersistenceState::Managed, ['name' => 'Alice']);

        self::assertSame(['name' => 'Alice'], $state->originalData);
    }

    public function testStateCanBeChanged(): void
    {
        $state = new ManagedObjectState(PersistenceState::New);

        $state->state = PersistenceState::Managed;

        self::assertSame(PersistenceState::Managed, $state->state);
    }

    public function testOriginalDataCanBeChanged(): void
    {
        $state = new ManagedObjectState(PersistenceState::Managed);

        $state->originalData = ['name' => 'Alice'];

        self::assertSame(['name' => 'Alice'], $state->originalData);
    }

    public function testOriginalDataCanBeResetToNull(): void
    {
        $state = new ManagedObjectState(PersistenceState::Managed, ['name' => 'Alice']);

        $state->originalData = null;

        self::assertNull($state->originalData);
    }

    public function testParentAssociationCanBeAssigned(): void
    {
        $state             = new ManagedObjectState(PersistenceState::Managed);
        $parentAssociation = new ParentAssociation(ParentAssociationTest::getAssociationFieldMapping(), null, 'embedded');

        $state->parentAssociation = $parentAssociation;

        self::assertSame($parentAssociation, $state->parentAssociation);
    }

    public function testIdentifierCanBeAssigned(): void
    {
        $state = new ManagedObjectState(PersistenceState::Managed);

        $state->identifier = 'abc123';

        self::assertSame('abc123', $state->identifier);
    }
}
