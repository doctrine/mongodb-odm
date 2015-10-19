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

        $this->assertSame(
            array('int' => $o->int, 'string' => $o->string, 'bool' => $o->bool, 'float' => $o->float),
            $persister->getShardKeyQuery($o)
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

        $shardKeyQuery = $persister->getShardKeyQuery($o);

        $this->assertInstanceOf('MongoId', $shardKeyQuery['oid']);
        $this->assertSame($o->oid, $shardKeyQuery['oid']->{'$id'});

        $this->assertInstanceOf('MongoBinData', $shardKeyQuery['bin']);
        $this->assertSame($o->bin, $shardKeyQuery['bin']->bin);

        $this->assertInstanceOf('MongoDate', $shardKeyQuery['date']);
        $this->assertSame($o->date->getTimestamp(), $shardKeyQuery['date']->sec);
        $this->assertSame(0, $shardKeyQuery['date']->usec);
    }

    public function testShardById()
    {
        $o = new ShardedById();
        $o->identifier = new \MongoId();

        /** @var DocumentPersister $persister */
        $persister = $this->uow->getDocumentPersister(get_class($o));
        $shardKeyQuery = $persister->getShardKeyQuery($o);

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

    /** @ODM\Int */
    public $int;

    /** @ODM\String */
    public $string;

    /** @ODM\Boolean */
    public $bool;

    /** @ODM\Float */
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

    /** @ODM\ObjectId */
    public $oid;

    /** @ODM\Bin */
    public $bin;

    /** @ODM\Date */
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