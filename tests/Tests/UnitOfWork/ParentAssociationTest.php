<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\UnitOfWork;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\UnitOfWork\ParentAssociation;
use PHPUnit\Framework\TestCase;
use stdClass;

/** @phpstan-import-type AssociationFieldMapping from ClassMetadata */
class ParentAssociationTest extends TestCase
{
    public function testProperties(): void
    {
        $mapping = self::getAssociationFieldMapping();
        $parent  = new stdClass();

        $association = new ParentAssociation($mapping, $parent, 'embedded');

        self::assertSame($mapping, $association->mapping);
        self::assertSame($parent, $association->parent);
        self::assertSame('embedded', $association->propertyPath);
    }

    public function testParentCanBeNull(): void
    {
        $association = new ParentAssociation(self::getAssociationFieldMapping(), null, 'embedded');

        self::assertNull($association->parent);
    }

    /** @phpstan-return AssociationFieldMapping */
    public static function getAssociationFieldMapping(): array
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
