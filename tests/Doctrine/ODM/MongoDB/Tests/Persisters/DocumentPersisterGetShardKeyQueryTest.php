<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\User;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use ReflectionMethod;

use function assert;
use function get_class;

class DocumentPersisterGetShardKeyQueryTest extends BaseTest
{
    public function testGetShardKeyQueryScalars(): void
    {
        $o         = new ShardedByScalars();
        $o->int    = 1;
        $o->string = 'hi';
        $o->bool   = true;
        $o->float  = 1.2;

        $persister = $this->uow->getDocumentPersister(get_class($o));
        assert($persister instanceof DocumentPersister);

        $method = new ReflectionMethod($persister, 'getShardKeyQuery');
        $method->setAccessible(true);

        $this->assertSame(
            ['int' => $o->int, 'string' => $o->string, 'bool' => $o->bool, 'float' => $o->float],
            $method->invoke($persister, $o)
        );
    }

    public function testGetShardKeyQueryObjects(): void
    {
        $o       = new ShardedByObjects();
        $o->oid  = '54ca2c4c81fec698130041a7';
        $o->bin  = 'hi';
        $o->date = new DateTime();

        $persister = $this->uow->getDocumentPersister(get_class($o));
        assert($persister instanceof DocumentPersister);

        $method = new ReflectionMethod($persister, 'getShardKeyQuery');
        $method->setAccessible(true);
        $shardKeyQuery = $method->invoke($persister, $o);

        $this->assertInstanceOf(ObjectId::class, $shardKeyQuery['oid']);
        $this->assertSame($o->oid, (string) $shardKeyQuery['oid']);

        $this->assertInstanceOf(Binary::class, $shardKeyQuery['bin']);
        $this->assertSame($o->bin, $shardKeyQuery['bin']->getData());

        $this->assertInstanceOf(UTCDateTime::class, $shardKeyQuery['date']);
        $this->assertEquals($o->date->getTimestamp(), $shardKeyQuery['date']->toDateTime()->getTimestamp());

        $this->assertSame(
            (int) $o->date->format('v'),
            (int) $shardKeyQuery['date']->toDateTime()->format('v')
        );
    }

    public function testShardById(): void
    {
        $o             = new ShardedById();
        $o->identifier = new ObjectId();

        $persister = $this->uow->getDocumentPersister(get_class($o));
        assert($persister instanceof DocumentPersister);

        $method = new ReflectionMethod($persister, 'getShardKeyQuery');
        $method->setAccessible(true);
        $shardKeyQuery = $method->invoke($persister, $o);

        $this->assertSame(['_id' => $o->identifier], $shardKeyQuery);
    }

    public function testShardByReference(): void
    {
        $o = new ShardedByReferenceOne();

        $userId       = new ObjectId();
        $o->reference = new User();
        $o->reference->setId($userId);

        $this->dm->persist($o->reference);

        $persister = $this->uow->getDocumentPersister(get_class($o));
        assert($persister instanceof DocumentPersister);

        $method = new ReflectionMethod($persister, 'getShardKeyQuery');
        $method->setAccessible(true);
        $shardKeyQuery = $method->invoke($persister, $o);

        $this->assertSame(['reference.$id' => $userId], $shardKeyQuery);
    }
}

/**
 * @ODM\Document
 * @ODM\ShardKey(keys={"int"="asc","string"="asc","bool"="asc","float"="asc"})
 */
class ShardedByScalars
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="int") */
    public $int;

    /** @ODM\Field(type="string") */
    public $string;

    /** @ODM\Field(type="bool") */
    public $bool;

    /** @ODM\Field(type="float") */
    public $float;
}

/**
 * @ODM\Document
 * @ODM\ShardKey(keys={"oid"="asc","bin"="asc","date"="asc"})
 */
class ShardedByObjects
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="object_id") */
    public $oid;

    /** @ODM\Field(type="bin") */
    public $bin;

    /** @ODM\Field(type="date") */
    public $date;
}

/**
 * @ODM\Document
 * @ODM\ShardKey(keys={"_id"="asc"})
 */
class ShardedById
{
    /** @ODM\Id */
    public $identifier;
}

/**
 * @ODM\Document
 * @ODM\ShardKey(keys={"reference"="asc"})
 */
class ShardedByReferenceOne
{
    /** @ODM\Id */
    public $id;

    /** @ODM\ReferenceOne(targetDocument=User::class) */
    public $reference;
}
