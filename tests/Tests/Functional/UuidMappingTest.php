<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use Symfony\Component\Uid\UuidV4;

class UuidMappingTest extends BaseTestCase
{
    public function testIdHasUuidType(): void
    {
        $metadata  = $this->dm->getClassMetadata(UuidTestDocument::class);
        $idMapping = $metadata->getIdentifierMapping();
        self::assertSame(Type::UUID, $idMapping['type'], 'Id field should have UUID type');
    }

    public function testExplicitValue(): void
    {
        $uuid     = new UuidV4();
        $document = new UuidTestDocument();

        $document->id                  = $uuid;
        $document->explicitlyTypedUuid = $uuid;
        $document->untypedUuid         = $uuid;

        $this->dm->persist($document);
        $this->dm->flush();

        $check = $this->dm->find(UuidTestDocument::class, $document->id);
        self::assertInstanceOf(UuidTestDocument::class, $check);
        self::assertEquals($uuid, $check->id);
        self::assertEquals($uuid, $check->explicitlyTypedUuid);
        self::assertEquals($uuid, $check->untypedUuid);
    }

    public function testAutoGenerateIdV4(): void
    {
        $document = new UuidTestDocument();

        $this->dm->persist($document);
        $this->dm->flush();

        $check = $this->dm->find(UuidTestDocument::class, $document->id);
        self::assertInstanceOf(UuidTestDocument::class, $check);
        self::assertInstanceOf(UuidV4::class, $check->id);
    }
}

#[ODM\Document]
class UuidTestDocument
{
    #[ODM\Id]
    public UuidV4 $id;

    #[ODM\Field(type: Type::UUID)]
    public ?UuidV4 $explicitlyTypedUuid = null;

    #[ODM\Field]
    public ?UuidV4 $untypedUuid = null;
}
