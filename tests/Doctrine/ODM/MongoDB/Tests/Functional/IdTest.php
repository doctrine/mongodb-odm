<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use DateTime;
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
    public function testUuidId()
    {
        $user = new UuidUser('Jonathan H. Wage');
        $this->dm->persist($user);
        $this->dm->flush();
        $id = $user->id;

        $this->dm->clear();
        $check1 = $this->dm->getRepository(UuidUser::class)->findOneBy(['id' => $id]);
        $this->assertNotNull($check1);

        $check2 = $this->dm->createQueryBuilder(UuidUser::class)
            ->field('id')->equals($id)->getQuery()->getSingleResult();
        $this->assertNotNull($check2);
        $this->assertSame($check1, $check2);

        $check3 = $this->dm->createQueryBuilder(UuidUser::class)
            ->field('name')->equals('Jonathan H. Wage')->getQuery()->getSingleResult();
        $this->assertNotNull($check3);
        $this->assertSame($check2, $check3);
    }

    public function testAlnumIdChars()
    {
        $user = new AlnumCharsUser('Jonathan H. Wage');
        $this->dm->persist($user);
        $user = new AlnumCharsUser('Kathrine R. Cage');
        $this->dm->persist($user);
        $this->dm->flush();

        $this->dm->clear();
        $check1 = $this->dm->getRepository(AlnumCharsUser::class)->findOneBy(['id' => 'x']);
        $this->assertNotNull($check1);

        $check2 = $this->dm->createQueryBuilder(AlnumCharsUser::class)
            ->field('id')->equals('x')->getQuery()->getSingleResult();
        $this->assertNotNull($check2);
        $this->assertSame($check1, $check2);

        $check3 = $this->dm->createQueryBuilder(AlnumCharsUser::class)
            ->field('name')->equals('Kathrine R. Cage')->getQuery()->getSingleResult();
        $this->assertNotNull($check3);
        $this->assertSame($check2, $check3);
    }

    public function testCollectionId()
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

        $this->assertEquals($user1->id, 1);
        $this->assertEquals($user2->id, 2);

        $this->assertEquals($reference1->id, 1);
        $this->assertEquals($reference2->id, 2);

        $check1 = $this->dm->getRepository(CollectionIdUser::class)->findOneBy(['id' => $user1->id]);
        $check2 = $this->dm->getRepository(CollectionIdUser::class)->findOneBy(['id' => $user2->id]);
        $this->assertNotNull($check1);
        $this->assertNotNull($check2);

        $this->assertEquals('referenced 1', $check1->reference->getName());
        $this->assertEquals('referenced 2', $check2->reference->getName());

        $check = $this->dm->getRepository(CollectionIdUser::class)->find($user1->id);
        $this->assertNotNull($check);
    }

    public function testCollectionIdWithStartingId()
    {
        $user1 = new CollectionIdUserWithStartingId('Jonathan H. Wage');
        $user2 = new CollectionIdUserWithStartingId('Jonathan H. Wage');

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertEquals($user1->id, 10);
        $this->assertEquals($user2->id, 11);
    }

    public function testEmbeddedDocumentWithId()
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

        $this->assertEquals($user1->id, 1);
        $this->assertEquals($user2->id, 2);

        $this->assertEquals($user1->embedded[0]->id, 1);
        $this->assertEquals($user1->embedded[1]->id, 2);

        $this->assertEquals($user2->embedded[0]->id, 3);
        $this->assertEquals($user2->embedded[1]->id, 4);
    }

    public function testIdGeneratorInstance()
    {
        $class = $this->dm->getClassMetadata(UuidUser::class);
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_UUID, $class->generatorType);
        $this->assertEquals(['salt' => 'test'], $class->generatorOptions);
        $this->assertInstanceOf(UuidGenerator::class, $class->idGenerator);
        $this->assertEquals('test', $class->idGenerator->getSalt());

        $serialized = serialize($class);
        $class      = unserialize($serialized);

        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_UUID, $class->generatorType);
        $this->assertEquals(['salt' => 'test'], $class->generatorOptions);
        $this->assertInstanceOf(UuidGenerator::class, $class->idGenerator);
        $this->assertEquals('test', $class->idGenerator->getSalt());
    }

    /**
     * @dataProvider provideEqualButNotIdenticalIds
     */
    public function testEqualButNotIdenticalIds($user1Id, $user2Id)
    {
        $this->assertNotSame($user1Id, $user2Id);

        $user1     = new CustomIdUser(sprintf('User1 with %s ID', gettype($user1Id)));
        $user1->id = $user1Id;

        $user2     = new CustomIdUser(sprintf('User2 with %s ID', gettype($user2Id)));
        $user2->id = $user2Id;

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertSame($user1->id, $user1Id);
        $this->assertSame($user2->id, $user2Id);

        $user1 = $this->dm->find(CustomIdUser::class, $user1Id);
        $user2 = $this->dm->find(CustomIdUser::class, $user2Id);

        $this->assertNotSame($user1, $user2);
        $this->assertSame($user1->id, $user1Id);
        $this->assertSame($user2->id, $user2Id);
    }

    public function provideEqualButNotIdenticalIds()
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
     * @dataProvider getTestIdTypesAndStrategiesData
     */
    public function testIdTypesAndStrategies($type, $strategy, $id = null, $expected = null, $expectedMongoType = null)
    {
        $className = $this->createIdTestClass($type, $strategy);

        $object     = new $className();
        $object->id = $id;

        $this->dm->persist($object);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertNotNull($object->id);

        if ($expectedMongoType !== null) {
            $check = $this->dm->getDocumentCollection(get_class($object))->findOne([]);
            $this->assertEquals($expectedMongoType, is_object($check['_id']) ? get_class($check['_id']) : gettype($check['_id']));
        }

        if ($expected !== null) {
            $this->assertEquals($expected, $object->id);
        }

        $object = $this->dm->find(get_class($object), $object->id);
        $this->assertNotNull($object);

        if ($expected !== null) {
            $this->assertEquals($expected, $object->id);
        }

        $object->test = 'changed';
        $this->dm->flush();
        $this->dm->clear();

        $object = $this->dm->find(get_class($object), $object->id);
        $this->assertEquals('changed', $object->test);
    }

    public function getTestIdTypesAndStrategiesData()
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

    /**
     * @dataProvider getTestBinIdsData
     */
    public function testBinIds($type, $expectedMongoBinDataType, $id)
    {
        $className = $this->createIdTestClass($type, 'none');

        $object     = new $className();
        $object->id = $id;

        $this->dm->persist($object);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection(get_class($object))->findOne([]);

        $this->assertEquals(Binary::class, get_class($check['_id']));
        $this->assertEquals($expectedMongoBinDataType, $check['_id']->getType());
    }

    public function getTestBinIdsData()
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

    public function testStrategyNoneAndNoIdThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Doctrine\ODM\MongoDB\Tests\Functional\CustomIdUser uses NONE identifier generation strategy but ' .
            'no identifier was provided when persisting.'
        );
        $this->dm->persist(new CustomIdUser('Maciej'));
    }

    public function testStrategyAutoWithNotValidIdThrowsException()
    {
        $user     = new TestIdTypesIdAutoUser();
        $user->id = 1;
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Doctrine\ODM\MongoDB\Tests\Functional\TestIdTypesIdAutoUser uses AUTO identifier generation strategy ' .
            'but provided identifier is not a valid ObjectId.'
        );
        $this->dm->persist($user);
    }

    private function createIdTestClass($type, $strategy)
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
                $type
            );

            eval($code);
        }

        return $className;
    }
}

/** @ODM\Document */
class UuidUser
{
    /** @ODM\Id(strategy="uuid", options={"salt"="test"}) */
    public $id;

    /** @ODM\Field(name="t", type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/** @ODM\Document */
class CollectionIdUser
{
    /** @ODM\Id(strategy="increment") */
    public $id;

    /** @ODM\Field(name="t", type="string") */
    public $name;

    /** @ODM\ReferenceOne(targetDocument=ReferencedCollectionId::class, cascade={"persist"}) */
    public $reference;

    /** @ODM\EmbedMany(targetDocument=EmbeddedCollectionId::class) */
    public $embedded = [];

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/** @ODM\Document */
class CollectionIdUserWithStartingId
{
    /** @ODM\Id(strategy="increment", options={"startingId"=10}) */
    public $id;

    /** @ODM\Field(name="t", type="string") */
    public $name;

    /** @ODM\ReferenceOne(targetDocument=ReferencedCollectionId::class, cascade={"persist"}) */
    public $reference;

    /** @ODM\EmbedMany(targetDocument=EmbeddedCollectionId::class) */
    public $embedded = [];

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/** @ODM\Document */
class ReferencedCollectionId
{
    /** @ODM\Id(strategy="increment") */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}

/** @ODM\EmbeddedDocument */
class EmbeddedCollectionId
{
    /** @ODM\Id(strategy="increment") */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}

/** @ODM\Document */
class AlnumCharsUser
{
    /** @ODM\Id(strategy="alnum", options={"chars"="zyxwvutsrqponmlkjihgfedcba"}) */
    public $id;

    /** @ODM\Field(name="t", type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/** @ODM\Document */
class CustomIdUser
{
    /** @ODM\Id(strategy="none",nullable=true) */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}

/** @ODM\Document */
class TestIdTypesIdAutoUser
{
    /** @ODM\Id(strategy="auto", options={"type"="id"}) **/
    public $id;
}
