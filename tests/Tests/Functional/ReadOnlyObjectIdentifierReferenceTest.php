<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\DocumentNotFoundException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use MongoDB\BSON\Binary;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

use function assert;

class ReadOnlyObjectIdentifierReferenceTest extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $configuration = $this->dm->getConfiguration();
        if ($configuration->isNativeLazyObjectEnabled() || $configuration->isLazyGhostObjectEnabled()) {
            return;
        }

        self::markTestSkipped('Read-only properties are not supported by the legacy Proxy Manager.');
    }

    public function testReadonlyObjectIdentifierReferencedFromEmbeddedDocument(): void
    {
        $uuid = new UuidV4();
        $this->insertRaw(ReadonlyUuidStation::class, ['_id' => new Binary($uuid->toBinary(), Binary::TYPE_UUID), 'name' => 'Station']);
        $this->insertRaw(ReadonlyUuidDailyPrice::class, ['station' => ['_id' => new Binary($uuid->toBinary(), Binary::TYPE_UUID)]]);
        $this->dm->clear();

        $price = $this->dm->getRepository(ReadonlyUuidDailyPrice::class)->findOneBy([]);
        assert($price instanceof ReadonlyUuidDailyPrice);

        $station = $price->station->getReferencedStation();
        self::assertTrue($this->uow->isUninitializedObject($station));
        self::assertEquals($uuid, $station->id);
        self::assertSame('Station', $station->name);
    }

    public function testReadonlyObjectIdentifierReference(): void
    {
        $station = new ReadonlyUuidStation(new UuidV4(), 'Station');
        $this->dm->persist($station);
        $this->dm->persist(new ReadonlyUuidDocumentWithReference($station));
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->getRepository(ReadonlyUuidDocumentWithReference::class)->findOneBy([]);
        assert($document instanceof ReadonlyUuidDocumentWithReference);

        self::assertTrue($this->uow->isUninitializedObject($document->station));
        self::assertEquals($station->id, $document->station->id);
        self::assertSame('Station', $document->station->name);
    }

    public function testReadonlyObjectIdentifierReferenceToMissingDocumentThrowsOnAccess(): void
    {
        $uuid = new UuidV4();
        $this->insertRaw(
            ReadonlyUuidDocumentWithReference::class,
            ['station' => new Binary($uuid->toBinary(), Binary::TYPE_UUID)],
        );

        $document = $this->dm->getRepository(ReadonlyUuidDocumentWithReference::class)->findOneBy([]);
        assert($document instanceof ReadonlyUuidDocumentWithReference);

        self::assertTrue($this->uow->isUninitializedObject($document->station));

        $this->expectException(DocumentNotFoundException::class);
        self::assertSame('Station', $document->station->name);
    }

    public function testReadonlyScalarIdentifierReference(): void
    {
        $target = new ReadonlyStringIdTarget('foo');
        $this->dm->persist($target);
        $this->dm->persist(new ReadonlyStringIdReferrer($target));
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->getRepository(ReadonlyStringIdReferrer::class)->findOneBy([]);
        assert($referrer instanceof ReadonlyStringIdReferrer);

        self::assertTrue($this->uow->isUninitializedObject($referrer->target));
        self::assertSame('foo', $referrer->target->id);
        self::assertSame('Target', $referrer->target->name);
    }

    public function testMutableObjectIdentifierReference(): void
    {
        $target = new MutableUuidIdTarget(new UuidV4());
        $this->dm->persist($target);
        $this->dm->persist(new MutableUuidIdReferrer($target));
        $this->dm->flush();
        $this->dm->clear();

        $referrer = $this->dm->getRepository(MutableUuidIdReferrer::class)->findOneBy([]);
        assert($referrer instanceof MutableUuidIdReferrer);

        self::assertTrue($this->uow->isUninitializedObject($referrer->target));
        self::assertEquals($target->id, $referrer->target->id);
        self::assertSame('Target', $referrer->target->name);
    }

    /** @param array<string, mixed> $document */
    private function insertRaw(string $class, array $document): void
    {
        $this->dm->getDocumentCollection($class)->insertOne($document);
    }
}

#[ODM\Document]
class ReadonlyUuidStation
{
    public function __construct(
        #[ODM\Id(type: Type::UUID, strategy: 'none')]
        public readonly Uuid $id,
        #[ODM\Field]
        public string $name = 'Station',
    ) {
    }
}

#[ODM\EmbeddedDocument]
class ReadonlyUuidPartialStation
{
    #[ODM\ReferenceOne(name: '_id', storeAs: 'id', targetDocument: ReadonlyUuidStation::class)]
    private ReadonlyUuidStation $referencedStation;

    public function getReferencedStation(): ReadonlyUuidStation
    {
        return $this->referencedStation;
    }
}

#[ODM\Document]
class ReadonlyUuidDailyPrice
{
    #[ODM\Id]
    public string $id;

    #[ODM\EmbedOne(targetDocument: ReadonlyUuidPartialStation::class)]
    public ReadonlyUuidPartialStation $station;
}

#[ODM\Document]
class ReadonlyUuidDocumentWithReference
{
    #[ODM\Id]
    public string $id;

    public function __construct(
        #[ODM\ReferenceOne(targetDocument: ReadonlyUuidStation::class, storeAs: 'id')]
        public ReadonlyUuidStation $station,
    ) {
    }
}

#[ODM\Document]
class ReadonlyStringIdTarget
{
    public function __construct(
        #[ODM\Id(type: 'string', strategy: 'none')]
        public readonly string $id,
        #[ODM\Field]
        public string $name = 'Target',
    ) {
    }
}

#[ODM\Document]
class ReadonlyStringIdReferrer
{
    #[ODM\Id]
    public string $id;

    public function __construct(
        #[ODM\ReferenceOne(targetDocument: ReadonlyStringIdTarget::class, storeAs: 'id')]
        public ReadonlyStringIdTarget $target,
    ) {
    }
}

#[ODM\Document]
class MutableUuidIdTarget
{
    public function __construct(
        #[ODM\Id(type: Type::UUID, strategy: 'none')]
        public Uuid $id,
        #[ODM\Field]
        public string $name = 'Target',
    ) {
    }
}

#[ODM\Document]
class MutableUuidIdReferrer
{
    #[ODM\Id]
    public string $id;

    public function __construct(
        #[ODM\ReferenceOne(targetDocument: MutableUuidIdTarget::class, storeAs: 'id')]
        public MutableUuidIdTarget $target,
    ) {
    }
}
