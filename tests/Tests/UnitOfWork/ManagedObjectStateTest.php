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

        self::assertSame(PersistenceState::New, $state->state);
        self::assertNull($state->originalData);
        self::assertNull($state->getParentAssociation());
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
