<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use ReflectionMethod;
use SimpleXMLElement;
use stdClass;

use function get_class;

use const DIRECTORY_SEPARATOR;

class XmlMappingDriverTest extends AbstractMappingDriverTestCase
{
    protected function loadDriver(): MappingDriver
    {
        return new XmlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'xml');
    }

    public function testSetShardKeyOptionsByAttributes(): void
    {
        $class   = new ClassMetadata(stdClass::class);
        $driver  = $this->loadDriver();
        $element = new SimpleXMLElement('<shard-key unique="true" numInitialChunks="4096"><key name="_id"/></shard-key>');

        /** @uses XmlDriver::setShardKey */
        $m = new ReflectionMethod(get_class($driver), 'setShardKey');
        $m->setAccessible(true);
        $m->invoke($driver, $class, $element);

        self::assertTrue($class->isSharded());
        $shardKey = $class->getShardKey();
        self::assertSame(['unique' => true, 'numInitialChunks' => 4096], $shardKey['options']);
        self::assertSame(['_id' => 1], $shardKey['keys']);
    }

    public function testInvalidMappingFileTriggersException(): void
    {
        $className     = InvalidMappingDocument::class;
        $mappingDriver = $this->loadDriver();

        $class = new ClassMetadata($className);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessageMatches("#Element '\{http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping\}field', attribute 'id': The attribute 'id' is not allowed.#");

        $mappingDriver->loadMetadataForClass($className, $class);
    }
}
