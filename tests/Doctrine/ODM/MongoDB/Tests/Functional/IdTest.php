<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class IdTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testUuidId()
    {
        $user = new UuidUser('Jonathan H. Wage');
        $this->dm->persist($user);
        $this->dm->flush();
        $id = $user->id;

        $this->dm->clear();
        $check1 = $this->dm->getRepository(__NAMESPACE__.'\UuidUser')->findOneBy(array('id' => $id));
        $this->assertNotNull($check1);

        $check2 = $this->dm->createQueryBuilder(__NAMESPACE__.'\UuidUser')
            ->field('id')->equals($id)->getQuery()->getSingleResult();
        $this->assertNotNull($check2);
        $this->assertSame($check1, $check2);

        $check3 = $this->dm->createQueryBuilder(__NAMESPACE__.'\UuidUser')
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
        $check1 = $this->dm->getRepository(__NAMESPACE__.'\AlnumCharsUser')->findOneBy(array('id' => 'x'));
        $this->assertNotNull($check1);

        $check2 = $this->dm->createQueryBuilder(__NAMESPACE__.'\AlnumCharsUser')
            ->field('id')->equals('x')->getQuery()->getSingleResult();
        $this->assertNotNull($check2);
        $this->assertSame($check1, $check2);

        $check3 = $this->dm->createQueryBuilder(__NAMESPACE__.'\AlnumCharsUser')
            ->field('name')->equals('Kathrine R. Cage')->getQuery()->getSingleResult();
        $this->assertNotNull($check3);
        $this->assertSame($check2, $check3);
    }

    public function testCollectionId()
    {
        $user1 = new CollectionIdUser('Jonathan H. Wage');
        $reference1 = new ReferencedCollectionId('referenced 1');
        $user1->reference = $reference1;

        $user2 = new CollectionIdUser('Jonathan H. Wage');

        $reference2 = new ReferencedCollectionId('referenced 2');
        $user2->reference = $reference2;

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertEquals($user1->id, 1);
        $this->assertEquals($user2->id, 2);

        $this->assertEquals($reference1->id, 1);
        $this->assertEquals($reference2->id, 2);

        $check1 = $this->dm->getRepository(__NAMESPACE__.'\CollectionIdUser')->findOneBy(array('id' => $user1->id));
        $check2 = $this->dm->getRepository(__NAMESPACE__.'\CollectionIdUser')->findOneBy(array('id' => $user2->id));
        $this->assertNotNull($check1);
        $this->assertNotNull($check2);

        $this->assertEquals('referenced 1', $check1->reference->getName());
        $this->assertEquals('referenced 2', $check2->reference->getName());

        $check = $this->dm->getRepository(__NAMESPACE__.'\CollectionIdUser')->find($user1->id);
        $this->assertNotNull($check);

    }

    public function testEmbeddedDocumentWithId()
    {
        $user1 = new CollectionIdUser('Jonathan H. Wage');
        $user1->embedded[] = new EmbeddedCollectionId('embedded #1');
        $user1->embedded[] = new EmbeddedCollectionId('embedded #2');
        $this->dm->persist($user1);
        $this->dm->flush();

        $user2 = new CollectionIdUser('Jonathan H. Wage');
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
        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\UuidUser');
        $this->assertEquals(\Doctrine\ODM\MongoDB\Mapping\ClassMetadata::GENERATOR_TYPE_UUID, $class->generatorType);
        $this->assertEquals(array('salt' => 'test'), $class->generatorOptions);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Id\UuidGenerator', $class->idGenerator);
        $this->assertEquals('test', $class->idGenerator->getSalt());

        $serialized = serialize($class);
        $class = unserialize($serialized);

        $this->assertEquals(\Doctrine\ODM\MongoDB\Mapping\ClassMetadata::GENERATOR_TYPE_UUID, $class->generatorType);
        $this->assertEquals(array('salt' => 'test'), $class->generatorOptions);
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Id\UuidGenerator', $class->idGenerator);
        $this->assertEquals('test', $class->idGenerator->getSalt());
    }

    /**
     * @dataProvider provideEqualButNotIdenticalIds
     */
    public function testEqualButNotIdenticalIds($user1Id, $user2Id)
    {
        /* Do not use assertEquals(), since the Scalar comparator tends to cast
         * scalars of different types to strings before comparison. We actually
         * want to check against PHP's loose equality logic here.
         */
        $this->assertTrue($user1Id == $user2Id);
        $this->assertNotSame($user1Id, $user2Id);

        $user1 = new CustomIdUser(sprintf('User1 with %s ID', gettype($user1Id)));
        $user1->id = $user1Id;

        $user2 = new CustomIdUser(sprintf('User2 with %s ID', gettype($user2Id)));
        $user2->id = $user2Id;

        $this->dm->persist($user1);
        $this->dm->persist($user2);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertSame($user1->id, $user1Id);
        $this->assertSame($user2->id, $user2Id);

        $user1 = $this->dm->find(__NAMESPACE__.'\CustomIdUser', $user1Id);
        $user2 = $this->dm->find(__NAMESPACE__.'\CustomIdUser', $user2Id);

        $this->assertNotSame($user1, $user2);
        $this->assertSame($user1->id, $user1Id);
        $this->assertSame($user2->id, $user2Id);
    }

    public function provideEqualButNotIdenticalIds()
    {
        /* MongoDB allows comparisons between different numeric types, so we
         * cannot test integer and floating point values (e.g. 123 and 123.0).
         *
         * See: http://docs.mongodb.org/manual/faq/developers/#what-is-the-compare-order-for-bson-types
         */
        return array(
            array('123', 123),
            array('123', 123.0),
            array('', 0),
            array('0', 0),
        );
    }

    /**
     * @dataProvider getTestIdTypesAndStrategiesData
     */
    public function testIdTypesAndStrategies($type, $strategy, $id = null, $expected = null, $expectedMongoType = null)
    {
        $className = $this->createIdTestClass($type, $strategy);

        $object = new $className();
        $object->id = $id;

        $this->dm->persist($object);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertNotNull($object->id);

        if ($expectedMongoType !== null) {
            $check = $this->dm->getDocumentCollection(get_class($object))->findOne(array());
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
        $mongoId = new \MongoId();

        return array(
            // boolean
            array('boolean', 'none', true,  true, 'boolean'),
            array('boolean', 'none', 1,  true, 'boolean'),
            array('boolean', 'none', false, false, 'boolean'),

            // integer
            array('int', 'none', 0, 0, 'integer'),
            array('int', 'none', 1, 1, 'integer'),
            array('int', 'none', '1', 1, 'integer'),
            array('int', 'increment', null, 1, 'integer'),

            // raw
            array('raw', 'none', 0, 0, 'integer'),
            array('raw', 'none', '1', '1', 'string'),
            array('raw', 'none', true, true, 'boolean'),
            array('raw', 'increment', null, 1, 'integer'),

            // float
            array('float', 'none', 1.1, 1.1, 'double'),
            array('float', 'none', '1.1', 1.1, 'double'),

            // string
            array('string', 'none', '', '', 'string'),
            array('string', 'none', 1, '1', 'string'),
            array('string', 'none', 'test', 'test', 'string'),
            array('string', 'increment', null, '1', 'string'),

            // custom_id
            array('custom_id', 'none', 0, 0, 'integer'),
            array('custom_id', 'none', '1', '1', 'string'),
            array('custom_id', 'increment', null, 1, 'integer'),

            // object_id
            array('object_id', 'none', (string) $mongoId, (string) $mongoId, 'MongoId'),

            // date
            array('date', 'none', new \DateTime(date('Y-m-d')), new \DateTime(date('Y-m-d')), 'MongoDate'),

            // bin
            array('bin', 'none', 'ABRWTIFGPEeSFf69fISAOA==', 'ABRWTIFGPEeSFf69fISAOA==', 'MongoBinData'),
            array('bin', 'uuid', null, null, 'MongoBinData'),
            array('bin_func', 'none', 'ABRWTIFGPEeSFf69fISAOA==', 'ABRWTIFGPEeSFf69fISAOA==', 'MongoBinData'),
            array('bin_bytearray', 'none', 'ABRWTIFGPEeSFf69fISAOA==', 'ABRWTIFGPEeSFf69fISAOA==', 'MongoBinData'),
            array('bin_uuid', 'none', 'ABRWTIFGPEeSFf69fISAOA==', 'ABRWTIFGPEeSFf69fISAOA==', 'MongoBinData'),
            array('bin_md5', 'none', 'ABRWTIFGPEeSFf69fISAOA==', 'ABRWTIFGPEeSFf69fISAOA==', 'MongoBinData'),
            array('bin_custom', 'none', 'ABRWTIFGPEeSFf69fISAOA==', 'ABRWTIFGPEeSFf69fISAOA==', 'MongoBinData'),

            // hash
            array('hash', 'none', array('key' => 'value'), array('key' => 'value'), 'array'),
        );
    }

    /**
     * @dataProvider getTestBinIdsData
     */
    public function testBinIds($type, $expectedMongoBinDataType)
    {
        $className = $this->createIdTestClass($type, 'none');

        $object = new $className();
        $object->id = 'ABRWTIFGPEeSFf69fISAOA==';

        $this->dm->persist($object);
        $this->dm->flush();
        $this->dm->clear();

        $check = $this->dm->getDocumentCollection(get_class($object))->findOne(array());

        $this->assertEquals('MongoBinData', get_class($check['_id']));
        $this->assertEquals($expectedMongoBinDataType, $check['_id']->type);
    }

    public function getTestBinIdsData()
    {
        return array(
            array('bin', 0),
            array('bin_func', \MongoBinData::FUNC),
            array('bin_bytearray', \MongoBinData::BYTE_ARRAY),
            array('bin_uuid', \MongoBinData::UUID),
            array('bin_md5', \MongoBinData::MD5),
            array('bin_custom', \MongoBinData::CUSTOM),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Doctrine\ODM\MongoDB\Tests\Functional\CustomIdUser uses NONE identifier generation strategy but no identifier was provided when persisting.
     */
    public function testStrategyNoneAndNoIdThrowsException()
    {
        $this->dm->persist(new CustomIdUser('Maciej'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Doctrine\ODM\MongoDB\Tests\Functional\TestIdTypesIdAutoUser uses AUTO identifier generation strategy but provided identifier is not valid MongoId.
     */
    public function testStrategyAutoWithNotValidIdThrowsException()
    {
        $this->createIdTestClass('id', 'auto');
        $user = new TestIdTypesIdAutoUser();
        $user->id = 1;
        $this->dm->persist($user);
    }

    private function createIdTestClass($type, $strategy)
    {
        $shortClassName = sprintf('TestIdTypes%s%sUser', ucfirst($type), ucfirst($strategy));
        $className = sprintf(__NAMESPACE__.'\\%s', $shortClassName);

        if (!class_exists($className)) {
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
}', $shortClassName, $strategy, $type);

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

    /** @ODM\ReferenceOne(targetDocument="ReferencedCollectionId", cascade={"persist"}) */
    public $reference;

    /** @ODM\EmbedMany(targetDocument="EmbeddedCollectionId") */
    public $embedded = array();

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
