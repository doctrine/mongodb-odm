<?php

declare(strict_types=1);

namespace Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Attribute\Document;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Field;
use Doctrine\ODM\MongoDB\Mapping\Attribute\Id;
use Doctrine\ODM\MongoDB\Mapping\Attribute\ReferenceOne;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class ReadOnlyPropertiesTest extends BaseTestCase
{
    public function testReadOnlyDocument(): void
    {
        $document           = new ReadOnlyProperties('Test Name');
        $document->onlyRead = new ReadOnlyProperties('Nested Name');
        $this->dm->persist($document);
        $this->dm->persist($document->onlyRead);
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(ReadOnlyProperties::class)->find($document->id);
        $this->assertEquals('Test Name', $document->name);
        $this->assertEquals('Nested Name', $document->onlyRead->name);
    }
}

#[Document]
class ReadOnlyProperties
{
    #[Id]
    public readonly string $id; // @phpstan-ignore property.uninitializedReadonly (initialized by reflection)

    #[Field]
    public readonly string $name;

    #[ReferenceOne(targetDocument: self::class)]
    public ?self $onlyRead;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}
