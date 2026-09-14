<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\UnitOfWork;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork\ManagedObjectState;
use Doctrine\ODM\MongoDB\UnitOfWork\PersistenceState;
use PHPUnit\Framework\TestCase;
use stdClass;

/** @phpstan-import-type AssociationFieldMapping from ClassMetadata */
class ManagedObjectStateTest extends TestCase
{
    public function testInitialState(): void
    {
        $state = new ManagedObjectState(PersistenceState::New);

        self::assertSame(PersistenceState::New, $state->getState());
        self::assertFalse($state->hasOriginalData());
        self::assertSame([], $state->getOriginalData());
        self::assertNull($state->getParentAssociation());
    }

    public function testConstructorAcceptsInitialOriginalData(): void
    {
        $state = new ManagedObjectState(PersistenceState::Managed, ['name' => 'Alice']);

        self::assertTrue($state->hasOriginalData());
        self::assertSame(['name' => 'Alice'], $state->getOriginalData());
    }

    public function testSetState(): void
    {
        $state = new ManagedObjectState(PersistenceState::New);

        $state->setState(PersistenceState::Managed);

        self::assertSame(PersistenceState::Managed, $state->getState());
    }

    public function testSetOriginalData(): void
    {
        $state = new ManagedObjectState(PersistenceState::Managed);

        $state->setOriginalData(['name' => 'Alice']);

        self::assertTrue($state->hasOriginalData());
        self::assertSame(['name' => 'Alice'], $state->getOriginalData());
    }

    public function testSetOriginalDataFieldOnEmptyState(): void
    {
        $state = new ManagedObjectState(PersistenceState::Managed);

        $state->setOriginalDataField('name', 'Alice');

        self::assertTrue($state->hasOriginalData());
        self::assertSame(['name' => 'Alice'], $state->getOriginalData());
    }

    public function testSetOriginalDataFieldUpdatesSingleField(): void
    {
        $state = new ManagedObjectState(PersistenceState::Managed, ['name' => 'Alice', 'age' => 30]);

        $state->setOriginalDataField('age', 31);

        self::assertSame(['name' => 'Alice', 'age' => 31], $state->getOriginalData());
    }

    public function testClearOriginalData(): void
    {
        $state = new ManagedObjectState(PersistenceState::Managed, ['name' => 'Alice']);

        $state->clearOriginalData();

        self::assertFalse($state->hasOriginalData());
        self::assertSame([], $state->getOriginalData());
    }

    public function testParentAssociation(): void
    {
        $state   = new ManagedObjectState(PersistenceState::Managed);
        $mapping = self::getAssociationFieldMapping();
        $parent  = new stdClass();

        $state->setParentAssociation($mapping, $parent, 'embedded');

        self::assertSame([$mapping, $parent, 'embedded'], $state->getParentAssociation());
    }

    public function testParentAssociationWithoutParent(): void
    {
        $state   = new ManagedObjectState(PersistenceState::Managed);
        $mapping = self::getAssociationFieldMapping();

        $state->setParentAssociation($mapping, null, 'embedded');

        self::assertSame([$mapping, null, 'embedded'], $state->getParentAssociation());
    }

    /** @phpstan-return AssociationFieldMapping */
    private static function getAssociationFieldMapping(): array
    {
        return [
            'fieldName' => 'embedded',
            'name' => 'embedded',
            'isCascadeRemove' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeMerge' => false,
            'isCascadeDetach' => false,
            'isOwningSide' => true,
            'isInverseSide' => false,
            'targetDocument' => stdClass::class,
            'association' => ClassMetadata::EMBED_ONE,
        ];
    }
}
