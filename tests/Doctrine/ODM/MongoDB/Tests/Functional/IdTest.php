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

        $check = $this->dm->getRepository(__NAMESPACE__.'\CollectionIdUser')->find((string) $user1->id);
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
}

/** @ODM\Document */
class UuidUser
{
    /** @ODM\Id(strategy="uuid", options={"salt"="test"}) */
    public $id;

    /** @ODM\String(name="t") */
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

    /** @ODM\String(name="t") */
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

    /** @ODM\String */
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

    /** @ODM\String */
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

    /** @ODM\String(name="t") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
