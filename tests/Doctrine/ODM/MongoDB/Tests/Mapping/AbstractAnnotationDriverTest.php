<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Documents\CmsUser;
use Generator;
use stdClass;

use function get_class;

abstract class AbstractAnnotationDriverTest extends AbstractMappingDriverTest
{
    public function testFieldInheritance(): void
    {
        // @TODO: This can be a generic test for all drivers
        $super  = $this->dm->getClassMetadata(AnnotationDriverTestSuper::class);
        $parent = $this->dm->getClassMetadata(AnnotationDriverTestParent::class);
        $child  = $this->dm->getClassMetadata(AnnotationDriverTestChild::class);

        self::assertFalse($super->hasField('id'), 'MappedSuperclass does not register its own public field');
        self::assertFalse($super->hasField('protected'), 'MappedSuperclass does not register its own protected field');
        self::assertTrue($super->hasField('private'), 'MappedSuperclass does register its own private field');
        self::assertFalse($super->isInheritedField('private'), 'MappedSuperclass does not inherit its own field');
        self::assertFalse($super->hasField('foo'), 'MappedSuperclass does not have field declared in child Document');
        self::assertFalse($super->hasField('bar'), 'MappedSuperclass does not have field declared in grandchild Document');

        self::assertArrayNotHasKey('declared', $super->fieldMappings['private'], 'MappedSuperclass does not track "declared" for non-inherited field');

        self::assertTrue($parent->hasField('id'), 'Document does have public field from MappedSuperclass parent');
        self::assertFalse($parent->isInheritedField('id'), 'Document does not inherit public field from MappedSuperclass parent');
        self::assertTrue($parent->hasField('protected'), 'Document does have protected field from MappedSuperclass parent');
        self::assertFalse($parent->isInheritedField('protected'), 'Document does not inherit protected field from MappedSuperclass parent');
        self::assertTrue($parent->hasField('private'), 'Document does have private field from MappedSuperclass parent');
        /* MappedSuperclass fields are never considered "inherited", but the
         * field is still considered "declared" in the MappedSuperclass, since
         * we need its ReflectionProperty to access it. This is a bit weird.
         */
        self::assertFalse($parent->isInheritedField('private'), 'Document does not inherit private field from MappedSuperclass parent');
        self::assertTrue($parent->hasField('foo'), 'Document does register its own public field');
        self::assertFalse($parent->isInheritedField('foo'), 'Document does not inherit its own field');
        self::assertFalse($parent->hasField('bar'), 'Document does not have field declared in child Document');

        self::assertArrayNotHasKey('declared', $parent->fieldMappings['id'], 'Document does not track "declared" for non-inherited public field from MappedSuperclass parent');
        self::assertArrayNotHasKey('declared', $parent->fieldMappings['protected'], 'Document does not track "declared" for non-inherited protected field from MappedSuperclass parent');
        self::assertEquals(AnnotationDriverTestSuper::class, $parent->fieldMappings['private']['declared'], 'Non-inherited private field from MappedSuperclass parent is declared in MappedSuperclass parent');
        self::assertArrayNotHasKey('declared', $parent->fieldMappings['foo'], 'Document does not track "declared" for its own public field');

        self::assertTrue($child->hasField('id'), 'Document does have public field from MappedSuperclass grandparent');
        self::assertTrue($child->isInheritedField('id'), 'Document does inherit public field from MappedSuperclass grandparent');
        self::assertTrue($child->hasField('protected'), 'Document does have protected field from MappedSuperclass grandparent');
        self::assertTrue($child->isInheritedField('protected'), 'Document does inherit protected field from MappedSuperclass grandparent');
        self::assertTrue($child->hasField('private'), 'Document does have private field from MappedSuperclass grandparent');
        self::assertTrue($child->isInheritedField('private'), 'Document does inherit private field from MappedSuperclass grandparent');
        self::assertTrue($child->hasField('foo'), 'Document does have public field from Document parent');
        self::assertTrue($child->isInheritedField('foo'), 'Document field declared in Document parent is inherited');
        self::assertTrue($child->hasField('bar'), 'Document does register its own public field');
        self::assertFalse($child->isInheritedField('bar'), 'Document does not inherit its own field');

        self::assertEquals(AnnotationDriverTestParent::class, $child->fieldMappings['id']['declared'], 'Inherited public field from MappedSuperclass grandparent is declared in Document parent');
        self::assertEquals(AnnotationDriverTestParent::class, $child->fieldMappings['protected']['declared'], 'Inherited protected field from MappedSuperclass grandparent is declared in Document parent');
        self::assertEquals(AnnotationDriverTestSuper::class, $child->fieldMappings['private']['declared'], 'Inherited private field from MappedSuperclass grandparent is declared in MappedSuperclass grandparent');
        self::assertEquals(AnnotationDriverTestParent::class, $child->fieldMappings['foo']['declared'], 'Inherited public field from Document parent is declared in Document parent');
    }

    /** @group DDC-268 */
    public function testLoadMetadataForNonDocumentThrowsException(): void
    {
        $cm               = new ClassMetadata('stdClass');
        $reader           = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $this->expectException(MappingException::class);
        $annotationDriver->loadMetadataForClass('stdClass', $cm);
    }

    /** @group DDC-268 */
    public function testColumnWithMissingTypeDefaultsToString(): void
    {
        $cm               = new ClassMetadata(ColumnWithoutType::class);
        $reader           = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $annotationDriver->loadMetadataForClass(stdClass::class, $cm);
        self::assertEquals('id', $cm->fieldMappings['id']['type']);
    }

    /** @group DDC-318 */
    public function testGetAllClassNamesIsIdempotent(): void
    {
        $annotationDriver = $this->loadDriverForCMSDocuments();
        $original         = $annotationDriver->getAllClassNames();

        $annotationDriver = $this->loadDriverForCMSDocuments();
        $afterTestReset   = $annotationDriver->getAllClassNames();

        self::assertEquals($original, $afterTestReset);
    }

    /** @group DDC-318 */
    public function testGetAllClassNamesIsIdempotentEvenWithDifferentDriverInstances(): void
    {
        $annotationDriver = $this->loadDriverForCMSDocuments();
        $original         = $annotationDriver->getAllClassNames();

        $annotationDriver = $this->loadDriverForCMSDocuments();
        $afterTestReset   = $annotationDriver->getAllClassNames();

        self::assertEquals($original, $afterTestReset);
    }

    /** @group DDC-318 */
    public function testGetAllClassNamesReturnsAlreadyLoadedClassesIfAppropriate(): void
    {
        $annotationDriver = $this->loadDriverForCMSDocuments();
        $classes          = $annotationDriver->getAllClassNames();

        self::assertContains(CmsUser::class, $classes);
    }

    /** @group DDC-318 */
    public function testGetClassNamesReturnsOnlyTheAppropriateClasses(): void
    {
        $extraneousClassName = ColumnWithoutType::class;

        $annotationDriver = $this->loadDriverForCMSDocuments();
        $classes          = $annotationDriver->getAllClassNames();

        self::assertNotContains($extraneousClassName, $classes);
    }

    public function testEmbeddedClassCantHaveShardKey(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Embedded document can\'t have shard key');
        $this->dm->getClassMetadata(AnnotationDriverEmbeddedWithShardKey::class);
    }

    public function testDocumentAnnotationCanSpecifyWriteConcern(): void
    {
        $cm = $this->dm->getClassMetadata(AnnotationDriverTestWriteConcernMajority::class);
        self::assertEquals('majority', $cm->writeConcern);

        $cm = $this->dm->getClassMetadata(AnnotationDriverTestWriteConcernUnacknowledged::class);
        self::assertSame(0, $cm->writeConcern);

        $cm = $this->dm->getClassMetadata(ColumnWithoutType::class);
        self::assertNull($cm->writeConcern);
    }

    /** @dataProvider provideClassCanBeMappedByOneAbstractDocument */
    public function testClassCanBeMappedByOneAbstractDocument(object $wrong, string $messageRegExp): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessageMatches($messageRegExp);

        $cm               = new ClassMetadata(get_class($wrong));
        $reader           = new AnnotationReader();
        $annotationDriver = new AnnotationDriver($reader);

        $annotationDriver->loadMetadataForClass(get_class($wrong), $cm);
    }

    public function provideClassCanBeMappedByOneAbstractDocument(): ?Generator
    {
        yield [
            /**
             * @ODM\Document()
             * @ODM\EmbeddedDocument
             */
            new class () {
            },
            '/as EmbeddedDocument because it was already mapped as Document\.$/',
        ];

        yield [
            /**
             * @ODM\Document()
             * @ODM\File
             */
            new class () {
            },
            '/as File because it was already mapped as Document\.$/',
        ];

        yield [
            /**
             * @ODM\Document()
             * @ODM\QueryResultDocument
             */
            new class () {
            },
            '/as QueryResultDocument because it was already mapped as Document\.$/',
        ];

        yield [
            /**
             * @ODM\Document()
             * @ODM\View
             */
            new class () {
            },
            '/as View because it was already mapped as Document\.$/',
        ];

        yield [
            /**
             * @ODM\Document()
             * @ODM\MappedSuperclass
             */
            new class () {
            },
            '/as MappedSuperclass because it was already mapped as Document\.$/',
        ];

        yield [
            /**
             * @ODM\MappedSuperclass()
             * @ODM\Document
             */
            new class () {
            },
            '/as Document because it was already mapped as MappedSuperclass\.$/',
        ];
    }

    public function testWrongValueForValidationValidatorShouldThrowException(): void
    {
        $annotationDriver = $this->loadDriver();
        $classMetadata    = new ClassMetadata(WrongValueForValidationValidator::class);
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The following schema validation error occurred while parsing the "validator" property of the "Doctrine\ODM\MongoDB\Tests\Mapping\WrongValueForValidationValidator" class: "Got parse error at "w", position 0: "SPECIAL_EXPECTED"" (code 0).');
        $annotationDriver->loadMetadataForClass($classMetadata->name, $classMetadata);
    }

    protected function loadDriverForCMSDocuments(): AnnotationDriver
    {
        $annotationDriver = $this->loadDriver();
        self::assertInstanceOf(AnnotationDriver::class, $annotationDriver);
        $annotationDriver->addPaths([__DIR__ . '/../../../../../Documents']);

        return $annotationDriver;
    }
}

/** @ODM\Document */
#[ODM\Document]
class ColumnWithoutType
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    public $id;
}

/** @ODM\MappedSuperclass */
#[ODM\MappedSuperclass]
class AnnotationDriverTestSuper
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    protected $protected;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    private $private;
}

/** @ODM\Document */
#[ODM\Document]
class AnnotationDriverTestParent extends AnnotationDriverTestSuper
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    public $foo;
}

/** @ODM\Document */
#[ODM\Document]
class AnnotationDriverTestChild extends AnnotationDriverTestParent
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    public $bar;
}

/**
 * @ODM\EmbeddedDocument
 * @ODM\ShardKey(keys={"foo"="asc"})
 */
#[ODM\EmbeddedDocument]
#[ODM\ShardKey(keys: ['foo' => 'asc'])]
class AnnotationDriverEmbeddedWithShardKey
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    #[ODM\Field(type: 'string')]
    public $foo;
}

/** @ODM\Document(writeConcern="majority") */
#[ODM\Document(writeConcern: 'majority')]
class AnnotationDriverTestWriteConcernMajority
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    public $id;
}

/** @ODM\Document(writeConcern=0) */
#[ODM\Document(writeConcern: 0)]
class AnnotationDriverTestWriteConcernUnacknowledged
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    public $id;
}

/** @ODM\Validation(validator="wrong") */
#[ODM\Validation(validator: 'wrong')]
class WrongValueForValidationValidator
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    #[ODM\Id]
    public $id;
}
