<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use Doctrine\ODM\MongoDB\Types\Versionable;
use MongoDB\BSON\Binary;
use PHPUnit\Framework\Attributes\After;
use ReflectionProperty;

use function assert;
use function is_int;

class GH2789Test extends BaseTestCase
{
    #[After]
    public function restoreTypeMap(): void
    {
        $r = new ReflectionProperty(Type::class, 'typesMap');
        $r->setValue(null, $r->getDefaultValue());
    }

    public function testVersionWithCustomType(): void
    {
        Type::addType(GH2789CustomType::class, GH2789CustomType::class);

        $doc = new GH2789VersionedUuid('original message');

        $this->dm->persist($doc);
        $this->dm->flush();

        $documents = $this->dm->getDocumentCollection(GH2789VersionedUuid::class)->find()->toArray();
        self::assertCount(1, $documents);
        self::assertEquals(new Binary('1', 142), $documents[0]['version'], 'The version field should be stored using the custom type');

        $doc->message = 'new message';
        $this->dm->persist($doc);
        $this->dm->flush();

        $documents = $this->dm->getDocumentCollection(GH2789VersionedUuid::class)->find()->toArray();
        self::assertCount(1, $documents);
        self::assertEquals(new Binary('2', 142), $documents[0]['version'], 'The version field should be incremented and stored using the custom type');
    }
}

#[ODM\Document(collection: 'gh2789_versioned_uuid')]
class GH2789VersionedUuid
{
    #[ODM\Id]
    public string $id;

    #[ODM\Version]
    #[ODM\Field(type: GH2789CustomType::class)]
    public int $version;

    public function __construct(
        #[ODM\Field(type: 'string')]
        public string $message,
    ) {
    }
}

/**
 * Custom type that stores an integer as a MongoDB Binary subtype 142.
 */
class GH2789CustomType extends Type implements Versionable
{
    public function convertToPHPValue(mixed $value): int
    {
        assert($value instanceof Binary);

        return (int) $value->getData();
    }

    public function convertToDatabaseValue(mixed $value): Binary
    {
        assert(is_int($value));

        return new Binary((string) $value, 142);
    }

    public function getNextVersion(mixed $current): int
    {
        if ($current === null) {
            return 1;
        }

        assert(is_int($current));

        return $current + 1;
    }
}
