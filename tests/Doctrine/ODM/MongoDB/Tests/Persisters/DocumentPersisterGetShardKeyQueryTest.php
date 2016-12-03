<?php

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class DocumentPersisterGetShardKeyQueryTest extends BaseTest
{
    public function testGetShardKeyQueryScalars()
    {
        $o = new ShardedByScalars();
        $o->int = 1;
        $o->string = 'hi';
        $o->bool = true;
        $o->float = 1.2;

        /** @var DocumentPersister $persister */
        $persister = $this->uow->getDocumentPersister(get_class($o));

        $method = new \ReflectionMethod($persister, 'getShardKeyQuery');
        $method->setAccessible(true);

        $this->assertSame(
            array('int' => $o->int, 'string' => $o->string, 'bool' => $o->bool, 'float' => $o->float),
            $method->invoke($persister, $o)
        );
    }

    public function testGetShardKeyQueryObjects()
    {
        $o = new ShardedByObjects();
        $o->oid = '54ca2c4c81fec698130041a7';
        $o->bin = 'hi';
        $o->date = new \DateTime();

        /** @var DocumentPersister $persister */
        $persister = $this->uow->getDocumentPersister(get_class($o));

        $method = new \ReflectionMethod($persister, 'getShardKeyQuery');
        $method->setAccessible(true);
        $shardKeyQuery = $method->invoke($persister, $o);

        $this->assertInstanceOf('MongoId', $shardKeyQuery['oid']);
        $this->assertSame($o->oid, $shardKeyQuery['oid']->{'$id'});

        $this->assertInstanceOf('MongoBinData', $shardKeyQuery['bin']);
        $this->assertSame($o->bin, $shardKeyQuery['bin']->bin);

        $this->assertInstanceOf('MongoDate', $shardKeyQuery['date']);
        $this->assertSame($o->date->getTimestamp(), $shardKeyQuery['date']->sec);

        $microseconds = (int)floor(((int)$o->date->format('u')) / 1000) * 1000;
        $this->assertSame($microseconds, $shardKeyQuery['date']->usec);
    }

    public function testShardById()
    {
        $o = new ShardedById();
        $o->identifier = new \MongoId();

        /** @var DocumentPersister $persister */
        $persister = $this->uow->getDocumentPersister(get_class($o));

        $method = new \ReflectionMethod($persister, 'getShardKeyQuery');
        $method->setAccessible(true);
        $shardKeyQuery = $method->invoke($persister, $o);

        $this->assertSame(array('_id' => $o->identifier), $shardKeyQuery);
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

    /** @ODM\Field(type="boolean") */
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
