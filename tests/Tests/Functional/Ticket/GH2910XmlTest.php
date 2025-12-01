<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

class GH2910XmlTest extends BaseTestCase
{
    public function testBirdCollectionNameIsAnimals(): void
    {
        $this->assertEquals('animals', $this->dm->getRepository(Bird2910Xml::class)->getClassMetadata()->getCollection());
    }

    public function testDogCollectionNameIsAnimals(): void
    {
        $this->assertEquals('animals', $this->dm->getRepository(Dog2910Xml::class)->getClassMetadata()->getCollection());
    }

    protected static function createMetadataDriverImpl(): MappingDriver
    {
        return new XmlDriver(__DIR__ . '/GH2910Xml');
    }
}

class Animal2910Xml
{
    private string $id;
}

class Bird2910Xml extends Animal2910Xml
{
}

class Dog2910Xml extends Animal2910Xml
{
}
