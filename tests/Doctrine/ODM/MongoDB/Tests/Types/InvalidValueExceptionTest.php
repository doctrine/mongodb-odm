<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Types\Type;
use PHPUnit\Framework\TestCase;

class InvalidValueExceptionTest extends TestCase
{
    public function testCollectionDoesntAcceptObject(): void
    {
        $t = Type::getType('collection');
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage(
            'Collection type requires value of type array or null, Doctrine\Common\Collections\ArrayCollection given',
        );
        $t->convertToDatabaseValue(new ArrayCollection());
    }

    public function testCollectionDoesntAcceptScalar(): void
    {
        $t = Type::getType('collection');
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage('Collection type requires value of type array or null, scalar given');
        $t->convertToDatabaseValue(true);
    }

    public function testHashDoesntAcceptObject(): void
    {
        $t = Type::getType('hash');
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage(
            'Hash type requires value of type array or null, Doctrine\Common\Collections\ArrayCollection given',
        );
        $t->convertToDatabaseValue(new ArrayCollection());
    }

    public function testHashDoesntAcceptScalar(): void
    {
        $t = Type::getType('hash');
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage('Hash type requires value of type array or null, scalar given');
        $t->convertToDatabaseValue(true);
    }
}
