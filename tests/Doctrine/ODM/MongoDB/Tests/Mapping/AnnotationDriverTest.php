<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Document;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

use function call_user_func;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;

use const E_USER_DEPRECATED;

class AnnotationDriverTest extends AbstractAnnotationDriverTestCase
{
    protected static function loadDriver(): MappingDriver
    {
        $reader = new AnnotationReader();

        return new AnnotationDriver($reader);
    }

    public function testIndexesClassAnnotationEmitsDeprecationMessage(): void
    {
        $driver        = static::loadDriver();
        $classMetadata = new ClassMetadata(DeprecatedIndexesClassAnnotation::class);

        $this->captureDeprecationMessages(
            static fn () => $driver->loadMetadataForClass($classMetadata->name, $classMetadata),
            $errors,
        );

        self::assertCount(1, $errors);
        self::assertSame(sprintf('Since doctrine/mongodb-odm 2.2: The "@Indexes" attribute used in class "%s" is deprecated. Specify all "@Index" and "@UniqueIndex" attributes on the class.', DeprecatedIndexesClassAnnotation::class), $errors[0]);

        $indexes = $classMetadata->indexes;

        self::assertTrue(isset($indexes[0]['keys']['foo']));
        self::assertEquals(1, $indexes[0]['keys']['foo']);
    }

    public function testIndexesOptionOfDocumentClassAnnotationEmitsDeprecationMessage(): void
    {
        $driver        = static::loadDriver();
        $classMetadata = new ClassMetadata(DeprecatedDocumentClassAnnotationIndexesOption::class);

        $this->captureDeprecationMessages(
            static fn () => $driver->loadMetadataForClass($classMetadata->name, $classMetadata),
            $errors,
        );

        self::assertCount(1, $errors);
        self::assertSame(sprintf('Since doctrine/mongodb-odm 2.2: The "indexes" parameter in the "%s" attribute for class "%s" is deprecated. Specify all "@Index" and "@UniqueIndex" attributes on the class.', Document::class, DeprecatedDocumentClassAnnotationIndexesOption::class), $errors[0]);

        $indexes = $classMetadata->indexes;

        self::assertTrue(isset($indexes[0]['keys']['foo']));
        self::assertEquals(1, $indexes[0]['keys']['foo']);
    }

    public function testIndexesPropertyAnnotationEmitsDeprecationMessage(): void
    {
        $driver        = static::loadDriver();
        $classMetadata = new ClassMetadata(DeprecatedIndexesPropertyAnnotation::class);

        $this->captureDeprecationMessages(
            static fn () => $driver->loadMetadataForClass($classMetadata->name, $classMetadata),
            $errors,
        );

        self::assertCount(1, $errors);
        self::assertSame(sprintf('Since doctrine/mongodb-odm 2.2: The "@Indexes" attribute used in property "foo" of class "%s" is deprecated. Specify all "@Index" and "@UniqueIndex" attributes on the class.', DeprecatedIndexesPropertyAnnotation::class), $errors[0]);

        $indexes = $classMetadata->indexes;

        self::assertTrue(isset($indexes[0]['keys']['foo']));
        self::assertEquals(1, $indexes[0]['keys']['foo']);
    }

    /** @param list<string>|null $errors */
    private function captureDeprecationMessages(callable $callable, ?array &$errors): mixed
    {
        /* TODO: this method can be replaced with expectUserDeprecationMessage() in PHPUnit 11+.
         * See: https://docs.phpunit.de/en/11.1/error-handling.html#expecting-deprecations-e-user-deprecated */
        $errors = [];

        set_error_handler(static function (int $errno, string $errstr) use (&$errors): bool {
            $errors[] = $errstr;

            return false;
        }, E_USER_DEPRECATED);

        try {
            return call_user_func($callable);
        } finally {
            restore_error_handler();
        }
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
