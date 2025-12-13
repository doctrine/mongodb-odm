<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\MongoDB\Tests\CaptureDeprecationMessages;
use Doctrine\ODM\MongoDB\Types\TypeRegistry;
use Doctrine\Persistence\Mapping\Driver\FileClassLocator;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use PHPUnit\Framework\Attributes\RequiresMethod;

use function class_exists;
use function sprintf;

#[RequiresMethod(AnnotationReader::class, '__construct')]
class AnnotationDriverTest extends AbstractAnnotationDriverTestCase
{
    use CaptureDeprecationMessages;

    protected static function loadDriver(array $paths = []): MappingDriver
    {
        if (class_exists(FileClassLocator::class)) {
            $paths = FileClassLocator::createFromDirectories($paths);
        }

        return AnnotationDriver::create($paths);
    }

    public function testIndexesClassAnnotationEmitsDeprecationMessage(): void
    {
        $driver        = static::loadDriver();
        $classMetadata = new ClassMetadata(DeprecatedIndexesClassAnnotation::class);
        $classMetadata->setTypeRegistry(new TypeRegistry());

        $this->captureDeprecationMessages(
            static fn () => $driver->loadMetadataForClass($classMetadata->name, $classMetadata),
            $errors,
        );

        self::assertEquals(
            [sprintf('Since doctrine/mongodb-odm 2.2: The "@Indexes" attribute used in class "%s" is deprecated. Specify all "@Index" and "@UniqueIndex" attributes on the class.', DeprecatedIndexesClassAnnotation::class)],
            $errors,
        );

        $indexes = $classMetadata->indexes;

        self::assertTrue(isset($indexes[0]['keys']['foo']));
        self::assertEquals(1, $indexes[0]['keys']['foo']);
    }

    public function testIndexesOptionOfDocumentClassAnnotationEmitsDeprecationMessage(): void
    {
        $driver        = static::loadDriver();
        $classMetadata = new ClassMetadata(DeprecatedDocumentClassAnnotationIndexesOption::class);
        $classMetadata->setTypeRegistry(new TypeRegistry());

        $this->captureDeprecationMessages(
            static fn () => $driver->loadMetadataForClass($classMetadata->name, $classMetadata),
            $errors,
        );

        self::assertEquals(
            [sprintf('Since doctrine/mongodb-odm 2.2: The "indexes" parameter in the "%s" attribute for class "%s" is deprecated. Specify all "@Index" and "@UniqueIndex" attributes on the class.', Document::class, DeprecatedDocumentClassAnnotationIndexesOption::class)],
            $errors,
        );

        $indexes = $classMetadata->indexes;

        self::assertTrue(isset($indexes[0]['keys']['foo']));
        self::assertEquals(1, $indexes[0]['keys']['foo']);
    }

    public function testIndexesPropertyAnnotationEmitsDeprecationMessage(): void
    {
        $driver        = static::loadDriver();
        $classMetadata = new ClassMetadata(DeprecatedIndexesPropertyAnnotation::class);
        $classMetadata->setTypeRegistry(new TypeRegistry());

        $this->captureDeprecationMessages(
            static fn () => $driver->loadMetadataForClass($classMetadata->name, $classMetadata),
            $errors,
        );

        self::assertEquals(
            sprintf('Since doctrine/mongodb-odm 2.2: The "@Indexes" attribute used in property "foo" of class "%s" is deprecated. Specify all "@Index" and "@UniqueIndex" attributes on the class.', DeprecatedIndexesPropertyAnnotation::class),
            $errors[0],
        );

        $indexes = $classMetadata->indexes;

        self::assertTrue(isset($indexes[0]['keys']['foo']));
        self::assertEquals(1, $indexes[0]['keys']['foo']);
    }
}

/**
 * @ODM\Document
 * @ODM\Indexes({
 *   @ODM\Index(keys={"foo"="asc"})
 * })
 */
class DeprecatedIndexesClassAnnotation
{
    /** @ODM\Id */
    public ?string $id;

    /** @ODM\Field(type="string") */
    public string $foo;
}

/**
 * @ODM\Document(indexes={
 *   @ODM\Index(keys={"foo"="asc"})
 * })
 */
class DeprecatedDocumentClassAnnotationIndexesOption
{
    /** @ODM\Id */
    public ?string $id;

    /** @ODM\Field(type="string") */
    public string $foo;
}

/** @ODM\Document */
class DeprecatedIndexesPropertyAnnotation
{
    /** @ODM\Id */
    public ?string $id;

    /**
     * @ODM\Field(type="string")
     * @ODM\Indexes({
     *   @ODM\Index
     * })
     */
    public string $foo;
}
