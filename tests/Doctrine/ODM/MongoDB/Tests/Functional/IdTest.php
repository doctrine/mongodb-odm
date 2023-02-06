<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Id\UuidGenerator;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use InvalidArgumentException;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

use function class_exists;
use function date;
use function get_class;
use function gettype;
use function is_object;
use function md5;
use function serialize;
use function sprintf;
use function ucfirst;
use function unserialize;

class IdTest extends BaseTest
{
    public function testUuidId(): void
    {
        $user = new UuidUser('Jonathan H. Wage');
        $this->dm->persist($user);
        $this->dm->flush();
        $id = $user->id;

        $this->dm->clear();
        $check1 = $this->dm->getRepository(UuidUser::class)->findOneBy(['id' => $id]);
        self::assertNotNull($check1);

        $check2 = $this->dm->createQueryBuilder(UuidUser::class)
            ->field('id')->equals($id)->getQuery()->getSingleResult();
        self::assertNotNull($check2);
        self::assertSame($check1, $check2);

        $check3 = $this->dm->createQueryBuilder(UuidUser::class)
            ->field('name')->equals('Jonathan H. Wage')->getQuery()->getSingleResult();
        self::assertNotNull($check3);
        self::assertSame($check2, $check3);
    }

    public function testAlnumIdChars(): void
    {
        $user = new AlnumCharsUser('Jonathan H. Wage');
        $this->dm->persist($user);
        $user = new AlnumCharsUser('Kathrine R. Cage');
        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->clear();
        $check1 = $this->dm->getRepository(AlnumCharsUser::class)->findOneBy(['id' => 'x']);
        self::assertNotNull($check1);

        $check2 = $this->dm->createQueryBuilder(AlnumCharsUser::class)
            ->field('id')->equals('x')->getQuery()->getSingleResult();
        self::assertNotNull($check2);
        self::assertSame($check1, $check2);

        $check3 = $this->dm->createQueryBuilder(AlnumCharsUser::class)
            ->field('name')->equals('Kathrine R. Cage')->getQuery()->getSingleResult();
        self::assertNotNull($check3);
        self::assertSame($check2, $check3);
    }

    public function testCollectionId(): void
    {
        $user1            = new CollectionIdUser('Jonathan H. Wage');
        $reference1       = new ReferencedCollectionId('referenced 1');
        $user1->reference = $reference1;

        $user2 = new CollectionIdUser('Jonathan H. Wage');

        $reference2       = new ReferencedCollectionId('referenced 2');
        $user2->reference = $reference2;

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->dm->clear();

        self::assertEquals(1, $user1->id);
        self::assertEquals(2, $user2->id);

        self::assertEquals(1, $reference1->id);
        self::assertEquals(2, $reference2->id);

        $check1 = $this->dm->getRepository(CollectionIdUser::class)->findOneBy(['id' => $user1->id]);
        $check2 = $this->dm->getRepository(CollectionIdUser::class)->findOneBy(['id' => $user2->id]);
        self::assertNotNull($check1);
        self::assertNotNull($check2);

        self::assertEquals('referenced 1', $check1->reference->getName());
        self::assertEquals('referenced 2', $check2->reference->getName());

        $check = $this->dm->getRepository(CollectionIdUser::class)->find($user1->id);
        self::assertNotNull($check);
    }

    public function testCollectionIdWithStartingId(): void
    {
        $user1 = new CollectionIdUserWithStartingId('Jonathan H. Wage');
        $user2 = new CollectionIdUserWithStartingId('Jonathan H. Wage');

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->dm->clear();

        self::assertEquals(10, $user1->id);
        self::assertEquals(11, $user2->id);
    }

    public function testEmbeddedDocumentWithId(): void
    {
        $user1             = new CollectionIdUser('Jonathan H. Wage');
        $user1->embedded[] = new EmbeddedCollectionId('embedded #1');
        $user1->embedded[] = new EmbeddedCollectionId('embedded #2');
        $this->dm->persist($user1);
        $this->dm->flush();

        $user2             = new CollectionIdUser('Jonathan H. Wage');
        $user2->embedded[] = new EmbeddedCollectionId('embedded #1');
        $user2->embedded[] = new EmbeddedCollectionId('embedded #2');
        $this->dm->persist($user2);
        $this->dm->flush();

        self::assertEquals(1, $user1->id);
        self::assertEquals(2, $user2->id);

        self::assertEquals(1, $user1->embedded[0]->id);
        self::assertEquals(2, $user1->embedded[1]->id);

        self::assertEquals(3, $user2->embedded[0]->id);
        self::assertEquals(4, $user2->embedded[1]->id);
    }

    public function testIdGeneratorInstance(): void
    {
        $class = $this->dm->getClassMetadata(UuidUser::class);
        self::assertEquals(ClassMetadata::GENERATOR_TYPE_UUID, $class->generatorType);
        self::assertEquals(['salt' => 'test'], $class->generatorOptions);
        self::assertInstanceOf(UuidGenerator::class, $class->idGenerator);
        self::assertEquals('test', $class->idGenerator->getSalt());

        $serialized = serialize($class);
        $class      = unserialize($serialized);

        self::assertEquals(ClassMetadata::GENERATOR_TYPE_UUID, $class->generatorType);
        self::assertEquals(['salt' => 'test'], $class->generatorOptions);
        self::assertInstanceOf(UuidGenerator::class, $class->idGenerator);
        self::assertEquals('test', $class->idGenerator->getSalt());
    }

    /**
     * @param int|float $user2Id
     *
     * @dataProvider provideEqualButNotIdenticalIds
     */
    public function testEqualButNotIdenticalIds(string $user1Id, $user2Id): void
    {
        self::assertNotSame($user1Id, $user2Id);

        $user1     = new CustomIdUser(sprintf('User1 with %s ID', gettype($user1Id)));
        $user1->id = $user1Id;

        $user2     = new CustomIdUser(sprintf('User2 with %s ID', gettype($user2Id)));
        $user2->id = $user2Id;

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->dm->clear();

        self::assertSame($user1->id, $user1Id);
        self::assertSame($user2->id, $user2Id);

        $user1 = $this->dm->find(CustomIdUser::class, $user1Id);
        $user2 = $this->dm->find(CustomIdUser::class, $user2Id);

        self::assertNotSame($user1, $user2);
        self::assertSame($user1->id, $user1Id);
        self::assertSame($user2->id, $user2Id);
    }

    public function provideEqualButNotIdenticalIds(): array
    {
        /* MongoDB allows comparisons between different numeric types, so we
         * cannot test integer and floating point values (e.g. 123 and 123.0).
         *
         * See: https://docs.mongodb.com/manual/faq/developers/#what-is-the-compare-order-for-bson-types
         */
        return [
            ['123', 123],
            ['123', 123.0],
            ['', 0],
            ['0', 0],
        ];
    }

    /**
     * @param mixed $id
     * @param mixed $expected
     *
     * @dataProvider getTestIdTypesAndStrategiesData
     */
    public function testIdTypesAndStrategies(string $type, string $strategy, $id = null, $expected = null, ?string $expectedMongoType = null): void
    {
        $className = $this->createIdTestClass($type, $strategy);

        $object     = new $className();
        $object->id = $id;

        $this->dm->persist($object);
        $this->dm->flush();
        $this->dm->clear();

        self::assertNotNull($object->id);

        if ($expectedMongoType !== null) {
            $check = $this->dm->getDocumentCollection(get_class($object))->findOne([]);
            self::assertEquals($expectedMongoType, is_object($check['_id']) ? get_class($check['_id']) : gettype($check['_id']));
        }

        if ($expected !== null) {
            self::assertEquals($expected, $object->id);
        }

        $object = $this->dm->find(get_class($object), $object->id);
        self::assertNotNull($object);

        if ($expected !== null) {
            self::assertEquals($expected, $object->id);
        }

        $object->test = 'changed';
        $this->dm->flush();
        $this->dm->clear();

        $object = $this->dm->find(get_class($object), $object->id);
        self::assertEquals('changed', $object->test);
    }

    public function getTestIdTypesAndStrategiesData(): array
    {
        $identifier = new ObjectId();

        return [
            // boolean
            ['boolean', 'none', true,  true, 'boolean'],
            ['boolean', 'none', 1,  true, 'boolean'],
            ['boolean', 'none', false, false, 'boolean'],

            // integer
            ['int', 'none', 0, 0, 'integer'],
            ['int', 'none', 1, 1, 'integer'],
            ['int', 'none', '1', 1, 'integer'],
            ['int', 'increment', null, 1, 'integer'],

            // raw
            ['raw', 'none', 0, 0, 'integer'],
            ['raw', 'none', '1', '1', 'string'],
            ['raw', 'none', true, true, 'boolean'],
            ['raw', 'increment', null, 1, 'integer'],

            // float
            ['float', 'none', 1.1, 1.1, 'double'],
            ['float', 'none', '1.1', 1.1, 'double'],

            // string
            ['string', 'none', '', '', 'string'],
            ['string', 'none', 1, '1', 'string'],
            ['string', 'none', 'test', 'test', 'string'],
            ['string', 'increment', null, '1', 'string'],

            // custom_id
            ['custom_id', 'none', 0, 0, 'integer'],
            ['custom_id', 'none', '1', '1', 'string'],
            ['custom_id', 'increment', null, 1, 'integer'],

            // object_id
            ['object_id', 'none', (string) $identifier, (string) $identifier, ObjectId::class],

            // date
            ['date', 'none', new DateTime(date('Y-m-d')), new DateTime(date('Y-m-d')), UTCDateTime::class],

            // bin
            ['bin', 'none', 'test-data', 'test-data', Binary::class],
            ['bin', 'uuid', null, null, Binary::class],
            ['bin_func', 'none', 'test-data', 'test-data', Binary::class],
            ['bin_bytearray', 'none', 'test-data', 'test-data', Binary::class],
            ['bin_uuid', 'none', 'TestTestTestTest', 'TestTestTestTest', Binary::class],
            ['bin_md5', 'none', md5('test'), md5('test'), Binary::class],
            ['bin_custom', 'none', 'test-data', 'test-data', Binary::class],

            // hash
            ['hash', 'none', ['key' => 'value'], ['key' => 'value'], 'array'],
        ];
    }

    /** @dataProvider getTestBinIdsData */
    public function testBinIds(string $type, int $expectedMongoBinDataType, string $id): void
    {
        $className = $this->createIdTestClass($type, 'none');

        $object     = new $className();
        $object->id = $id;

        $this->dm->persist($object);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection(get_class($object))->findOne([]);

        self::assertEquals(Binary::class, get_class($check['_id']));
        self::assertEquals($expectedMongoBinDataType, $check['_id']->getType());
    }

    public function getTestBinIdsData(): array
    {
        return [
            ['bin', 0, 'test-data'],
            ['bin_func', Binary::TYPE_FUNCTION, 'test-data'],
            ['bin_bytearray', Binary::TYPE_OLD_BINARY, 'test-data'],
            ['bin_uuid', Binary::TYPE_OLD_UUID, 'testtesttesttest'],
            ['bin_md5', Binary::TYPE_MD5, md5('test')],
            ['bin_custom', Binary::TYPE_USER_DEFINED, 'test-data'],
        ];
    }

    public function testStrategyNoneAndNoIdThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Doctrine\ODM\MongoDB\Tests\Functional\CustomIdUser uses NONE identifier generation strategy but ' .
            'no identifier was provided when persisting.',
        );
        $this->dm->persist(new CustomIdUser('Maciej'));
    }

    public function testStrategyAutoWithNotValidIdThrowsException(): void
    {
        $user     = new TestIdTypesIdAutoUser();
        $user->id = 1;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Doctrine\ODM\MongoDB\Tests\Functional\TestIdTypesIdAutoUser uses AUTO identifier generation strategy ' .
            'but provided identifier is not a valid ObjectId.',
        );
        $this->dm->persist($user);
    }

    private function createIdTestClass(string $type, string $strategy): string
    {
        $shortClassName = sprintf('TestIdTypes%s%sUser', ucfirst($type), ucfirst($strategy));
        $className      = sprintf(__NAMESPACE__ . '\\%s', $shortClassName);

        if (! class_exists($className)) {
            $code = sprintf(
                'namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** @Doctrine\ODM\MongoDB\Mapping\Annotations\Document */
class %s
{
    /** @Doctrine\ODM\MongoDB\Mapping\Annotations\Id(strategy="%s", options={"type"="%s"}) **/
    public $id;

    /** @Doctrine\ODM\MongoDB\Mapping\Annotations\Field("type=string") **/
    public $test = "test";
}',
                $shortClassName,
                $strategy,
                $type,
            );

            eval($code);
        }

        return $className;
    }
}

/** @ODM\Document */
class UuidUser
{
    /**
     * @ODM\Id(strategy="uuid", options={"salt"="test"})
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(name="t", type="string")
     *
     * @var string
     */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/** @ODM\Document */
class CollectionIdUser
{
    /**
     * @ODM\Id(strategy="increment")
     *
     * @var int|null
     */
    public $id;

    /**
     * @ODM\Field(name="t", type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument=ReferencedCollectionId::class, cascade={"persist"})
     *
     * @var ReferencedCollectionId|null
     */
    public $reference;

    /**
     * @ODM\EmbedMany(targetDocument=EmbeddedCollectionId::class)
     *
     * @var Collection<int, EmbeddedCollectionId>|array<EmbeddedCollectionId>
     */
    public $embedded = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/** @ODM\Document */
class CollectionIdUserWithStartingId
{
    /**
     * @ODM\Id(strategy="increment", options={"startingId"=10})
     *
     * @var int|null
     */
    public $id;

    /**
     * @ODM\Field(name="t", type="string")
     *
     * @var string
     */
    public $name;

    /**
     * @ODM\ReferenceOne(targetDocument=ReferencedCollectionId::class, cascade={"persist"})
     *
     * @var ReferencedCollectionId|null
     */
    public $reference;

    /**
     * @ODM\EmbedMany(targetDocument=EmbeddedCollectionId::class)
     *
     * @var Collection<int, EmbeddedCollectionId>|array<EmbeddedCollectionId>
     */
    public $embedded = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/** @ODM\Document */
class ReferencedCollectionId
{
    /**
     * @ODM\Id(strategy="increment")
     *
     * @var int|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

/** @ODM\EmbeddedDocument */
class EmbeddedCollectionId
{
    /**
     * @ODM\Id(strategy="increment")
     *
     * @var int|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

/** @ODM\Document */
class AlnumCharsUser
{
    /**
     * @ODM\Id(strategy="alnum", options={"chars"="zyxwvutsrqponmlkjihgfedcba"})
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(name="t", type="string")
     *
     * @var string
     */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/** @ODM\Document */
class CustomIdUser
{
    /**
     * @ODM\Id(strategy="none", nullable=true)
     *
     * @var int|string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    public $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

/** @ODM\Document */
class TestIdTypesIdAutoUser
{
    /**
     * @ODM\Id(strategy="auto", options={"type"="id"})
     *
     * @var int|null
     */
    public $id;
}
