<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class ShardingTest extends BaseTest
{
    public function testSimpleShardDBRef()
    {
        $shard = new SimpleShardDocument();
        $shard->shardKey = $shardKey = 'foo';
        $shard->name = 'bar';

        $shardRef = new ShardRefDocument();
        $shardRef->name = 'foo';
        $shardRef->simpleShardReference = $shard;

        $this->dm->persist($shard);
        $this->dm->persist($shardRef);
        $this->dm->flush();
        $this->dm->clear();

        $shardRef = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\ShardRefDocument')
            ->field('name')->equals('foo')
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        $this->assertTrue(isset($shardRef['simpleShardReference']['$sk']));
        $this->assertEquals($shardKey, $shardRef['simpleShardReference']['$sk']);
    }

    public function testSimpleShardReference()
    {
        $shard = new SimpleShardDocument();
        $shard->shardKey = $shardKey = 'foo';
        $shard->name = 'bar';

        $shardRef = new ShardRefDocument();
        $shardRef->name = 'foo';
        $shardRef->simpleShardReference = $shard;

        $this->dm->persist($shard);
        $this->dm->persist($shardRef);
        $this->dm->flush();
        $this->dm->clear();

        $shardRef = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\ShardRefDocument')
            ->field('name')->equals('foo')
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($shardKey, $shardRef->simpleShardReference->getShardKey());
    }

    public function testComplexShardDBRef()
    {
        $shard = new ComplexShardDocument();
        $shard->shardKey = $shardKey = 'foo';
        $shard->name = $name = 'bar';
        $shard->text = $text = 'test';

        $shardRef = new ShardRefDocument();
        $shardRef->name = 'foo';
        $shardRef->complexShardReference = $shard;

        $this->dm->persist($shard);
        $this->dm->persist($shardRef);
        $this->dm->flush();
        $this->dm->clear();

        $shardRef = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\ShardRefDocument')
            ->field('name')->equals('foo')
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        $this->assertTrue(isset($shardRef['complexShardReference']['$sk']));
        $this->assertEquals($shardKey, $shardRef['complexShardReference']['$sk']);
        $this->assertTrue(isset($shardRef['complexShardReference']['$n']));
        $this->assertEquals($name, $shardRef['complexShardReference']['$n']);
        $this->assertTrue(isset($shardRef['complexShardReference']['$text']));
        $this->assertEquals($text, $shardRef['complexShardReference']['$text']);
    }

    public function testComplexShardReference()
    {
        $shard = new ComplexShardDocument();
        $shard->shardKey = $shardKey = 'foo';
        $shard->name = $name = 'bar';
        $shard->text = $text = 'test';

        $shardRef = new ShardRefDocument();
        $shardRef->name = 'foo';
        $shardRef->complexShardReference = $shard;

        $this->dm->persist($shard);
        $this->dm->persist($shardRef);
        $this->dm->flush();
        $this->dm->clear();

        $shardRef = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\ShardRefDocument')
            ->field('name')->equals('foo')
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($shardKey, $shardRef->complexShardReference->getShardKey());
        $this->assertEquals($name, $shardRef->complexShardReference->getName());
        $this->assertEquals($text, $shardRef->complexShardReference->getText());
    }

    public function testManySimpleShardDBRef()
    {
        $shardRef = new ShardRefDocument();
        $shardRef->name = 'foo';
        $numShards = 2;
        for($i = 1; $i <= $numShards; $i++) {
            $shard = new SimpleShardDocument();
            $shard->shardKey = 'foo' . $i;
            $shard->name = 'bar' . $i;

            $shardRef->shardReferences[] = $shard;
        }

        $this->dm->persist($shardRef);
        $this->dm->flush();
        $this->dm->clear();

        $shardRef = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\ShardRefDocument')
            ->field('name')->equals('foo')
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($numShards, count($shardRef['shardReferences']));
        for($i = 0; $i < $numShards; $i++) {
            $this->assertTrue(isset($shardRef['shardReferences'][$i]['$sk']));
            $this->assertEquals('foo' . ($i + 1), $shardRef['shardReferences'][$i]['$sk']);
        }
    }

    public function testManySimpleShardReferences()
    {
        $shardRef = new ShardRefDocument();
        $shardRef->name = 'foo';
        $numShards = 2;
        for($i = 1; $i <= $numShards; $i++) {
            $shard = new SimpleShardDocument();
            $shard->shardKey = 'foo' . $i;
            $shard->name = 'bar' . $i;

            $shardRef->shardReferences[] = $shard;
        }

        $this->dm->persist($shardRef);
        $this->dm->flush();
        $this->dm->clear();

        $shardRef = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\ShardRefDocument')
            ->field('name')->equals('foo')
            ->getQuery()
            ->getSingleResult();

        $this->assertEquals($numShards, count($shardRef->shardReferences));
        for($i = 0; $i < $numShards; $i++) {
            $this->assertEquals('foo' . ($i + 1), $shardRef->shardReferences[$i]->getShardKey());
        }
    }

    public function testEmbeddedShardDBRef()
    {
        $this->markTestIncomplete('Embedded shard keys not supported.');

        $embedShard = new EmbeddedShardKeyDocument();
        $embedShard->shardKey = $shardKey = 'foo';

        $shard = new EmbeddedShardDocument();
        $shard->name = 'bar';
        $shard->embedOne = $embedShard;

        $shardRef = new ShardRefDocument();
        $shardRef->name = 'foo';
        $shardRef->embeddedShardReference = $shard;

        $this->dm->persist($shard);
        $this->dm->persist($shardRef);
        $this->dm->flush();
        $this->dm->clear();

        $shardRef = $this->dm->createQueryBuilder('Doctrine\ODM\MongoDB\Tests\Functional\ShardRefDocument')
            ->field('name')->equals('foo')
            ->hydrate(false)
            ->getQuery()
            ->getSingleResult();

        $this->assertTrue(isset($shardRef['embeddedShardReference']['$embedOne.sk']));
        $this->assertEquals($shardKey, $shardRef['embeddedShardReference']['$embedOne.sk']);
    }
}

/**
 * @ODM\Document
 */
class ShardRefDocument
{
    /** @ODM\Id */
    public $id;

     /** @ODM\String */
    public $name;

   /** @ODM\ReferenceOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\SimpleShardDocument") */
    public $simpleShardReference;

    /** @ODM\ReferenceOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\ComplexShardDocument") */
    public $complexShardReference;

    /** @ODM\ReferenceMany(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\SimpleShardDocument", cascade={"all"}) */
    public $shardReferences = array();

    /** @ODM\ReferenceOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\EmbeddedShardDocument") */
    public $embeddedShardReference;
}

/**
 * @ODM\Document
 * @ODM\QueryFields({"shardKey"})
 */
class SimpleShardDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String(name="sk") */
    public $shardKey;

    /** @ODM\String */
    public $name;

    public function getShardKey()
    {
        return $this->shardKey;
    }
}

/**
 * @ODM\Document
 * @ODM\QueryFields({"shardKey", "name", "text"})
 */
class ComplexShardDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String(name="sk") */
    public $shardKey;

    /** @ODM\String(name="n") */
    public $name;

    /** @ODM\String */
    public $text;

    public function getShardKey()
    {
        return $this->shardKey;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getText()
    {
        return $this->text;
    }
}

/**
 * @ODM\Document
 * @ODM\QueryFields({"embedOne.shardKey"})
 */
class EmbeddedShardDocument
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String(name="n") */
    public $name;

    /** @ODM\EmbedOne(targetDocument="Doctrine\ODM\MongoDB\Tests\Functional\EmbeddedShardKeyDocument") */
    public $embedOne;

    public function getName()
    {
        return $this->name;
    }

    public function getEmbedOne()
    {
        return $this->embedOne;
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class EmbeddedShardKeyDocument
{
    /** @ODM\String(name="sk") */
    public $shardKey;
}
