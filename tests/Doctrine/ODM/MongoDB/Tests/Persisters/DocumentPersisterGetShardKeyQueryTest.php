<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Persisters;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\User;
use MongoDB\BSON\Binary;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use ReflectionMethod;

class DocumentPersisterGetShardKeyQueryTest extends BaseTestCase
{
    public function testGetShardKeyQueryScalars(): void
    {
        $o         = new ShardedByScalars();
        $o->int    = 1;
        $o->string = 'hi';
        $o->bool   = true;
        $o->float  = 1.2;

        $persister = $this->uow->getDocumentPersister($o::class);

        $method = new ReflectionMethod($persister, 'getShardKeyQuery');
        $method->setAccessible(true);

        self::assertSame(
            ['int' => $o->int, 'string' => $o->string, 'bool' => $o->bool, 'float' => $o->float],
            $method->invoke($persister, $o),
        );
    }

    public function testGetShardKeyQueryObjects(): void
    {
        $o       = new ShardedByObjects();
        $o->oid  = '54ca2c4c81fec698130041a7';
        $o->bin  = 'hi';
        $o->date = new DateTime();

        $persister = $this->uow->getDocumentPersister($o::class);

        $method = new ReflectionMethod($persister, 'getShardKeyQuery');
        $method->setAccessible(true);
        $shardKeyQuery = $method->invoke($persister, $o);

        self::assertInstanceOf(ObjectId::class, $shardKeyQuery['oid']);
        self::assertSame($o->oid, (string) $shardKeyQuery['oid']);

        self::assertInstanceOf(Binary::class, $shardKeyQuery['bin']);
        self::assertSame($o->bin, $shardKeyQuery['bin']->getData());

        self::assertInstanceOf(UTCDateTime::class, $shardKeyQuery['date']);
        self::assertEquals($o->date->getTimestamp(), $shardKeyQuery['date']->toDateTime()->getTimestamp());

        self::assertSame(
            (int) $o->date->format('v'),
            (int) $shardKeyQuery['date']->toDateTime()->format('v'),
        );
    }

    public function testShardById(): void
    {
        $o             = new ShardedById();
        $o->identifier = new ObjectId();

        $persister = $this->uow->getDocumentPersister($o::class);

        $method = new ReflectionMethod($persister, 'getShardKeyQuery');
        $method->setAccessible(true);
        $shardKeyQuery = $method->invoke($persister, $o);

        self::assertSame(['_id' => $o->identifier], $shardKeyQuery);
    }

    public function testShardByReference(): void
    {
        $o = new ShardedByReferenceOne();

        $userId       = new ObjectId();
        $o->reference = new User();
        $o->reference->setId($userId);

        $this->dm->persist($o->reference);

        $persister = $this->uow->getDocumentPersister($o::class);

        $method = new ReflectionMethod($persister, 'getShardKeyQuery');
        $method->setAccessible(true);
        $shardKeyQuery = $method->invoke($persister, $o);

        self::assertSame(['reference.$id' => $userId], $shardKeyQuery);
    }
}

#[ODM\Document]
#[ODM\ShardKey(keys: ['int' => 'asc', 'string' => 'asc', 'bool' => 'asc', 'float' => 'asc'])]
class ShardedByScalars
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var int */
    #[ODM\Field(type: 'int')]
    public $int;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $string;

    /** @var bool|null */
    #[ODM\Field(type: 'bool')]
    public $bool;

    /** @var float|null */
    #[ODM\Field(type: 'float')]
    public $float;
}

#[ODM\Document]
#[ODM\ShardKey(keys: ['oid' => 'asc', 'bin' => 'asc', 'date' => 'asc'])]
class ShardedByObjects
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'object_id')]
    public $oid;

    /** @var string|null */
    #[ODM\Field(type: 'bin')]
    public $bin;

    /** @var DateTime|null */
    #[ODM\Field(type: 'date')]
    public $date;
}

#[ODM\Document]
#[ODM\ShardKey(keys: ['_id' => 'asc'])]
class ShardedById
{
    /** @var ObjectId|null */
    #[ODM\Id]
    public $identifier;
}

#[ODM\Document]
#[ODM\ShardKey(keys: ['reference' => 'asc'])]
class ShardedByReferenceOne
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var User|null */
    #[ODM\ReferenceOne(targetDocument: User::class)]
    public $reference;
}
