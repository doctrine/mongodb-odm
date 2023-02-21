<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Symfony;

use Doctrine\Persistence\Mapping\Driver\FileDriver;
use Doctrine\Persistence\Mapping\MappingException;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function touch;
use function unlink;

/** @group DDC-1418 */
abstract class AbstractDriverTest extends TestCase
{
    protected string $dir;

    public function testFindMappingFile(): void
    {
        $driver = $this->getDriver([
            'MyNamespace\MySubnamespace\DocumentFoo' => 'foo',
            'MyNamespace\MySubnamespace\Document' => $this->dir,
        ]);

        touch($filename = $this->dir . '/Foo' . $this->getFileExtension());
        self::assertEquals($filename, $driver->getLocator()->findMappingFile('MyNamespace\MySubnamespace\Document\Foo'));
    }

    public function testFindMappingFileInSubnamespace(): void
    {
        $driver = $this->getDriver([
            'MyNamespace\MySubnamespace\Document' => $this->dir,
        ]);

        touch($filename = $this->dir . '/Foo.Bar' . $this->getFileExtension());
        self::assertEquals($filename, $driver->getLocator()->findMappingFile('MyNamespace\MySubnamespace\Document\Foo\Bar'));
    }

    public function testFindMappingFileNamespacedFoundFileNotFound(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('No mapping file found named');

        $driver = $this->getDriver([
            'MyNamespace\MySubnamespace\Document' => $this->dir,
        ]);

        $driver->getLocator()->findMappingFile('MyNamespace\MySubnamespace\Document\Foo');
    }

    public function testFindMappingNamespaceNotFound(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage("No mapping file found named 'Foo" . $this->getFileExtension() . "' for class 'MyOtherNamespace\MySubnamespace\Document\Foo'.");

        $driver = $this->getDriver([
            'MyNamespace\MySubnamespace\Document' => $this->dir,
        ]);

        $driver->getLocator()->findMappingFile('MyOtherNamespace\MySubnamespace\Document\Foo');
    }

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/abstract_driver_test';
        @mkdir($this->dir, 0775, true);
    }

    protected function tearDown(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->dir), RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($iterator as $path) {
            if ($path->isDir()) {
                @rmdir((string) $path);
            } else {
                @unlink((string) $path);
            }
        }

        @rmdir($this->dir);
    }

    abstract protected function getFileExtension(): string;

    /**
     * @param string[] $paths
     *
     * @return FileDriver
     */
    abstract protected function getDriver(array $paths = []);
}
