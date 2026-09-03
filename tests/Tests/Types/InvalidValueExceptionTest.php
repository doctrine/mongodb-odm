<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Types;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\MongoDBException;
use Doctrine\ODM\MongoDB\Types\CollectionType;
use Doctrine\ODM\MongoDB\Types\HashType;
use PHPUnit\Framework\TestCase;

class InvalidValueExceptionTest extends TestCase
{
    public function testCollectionDoesntAcceptObject(): void
    {
        $type = new CollectionType();
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage(
            'Collection type requires value of type array or null, Doctrine\Common\Collections\ArrayCollection given',
        );
        $type->convertToDatabaseValue(new ArrayCollection());
    }

    public function testCollectionDoesntAcceptScalar(): void
    {
        $type = new CollectionType();
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage('Collection type requires value of type array or null, scalar given');
        $type->convertToDatabaseValue(true);
    }

    public function testHashDoesntAcceptObject(): void
    {
        $type = new HashType();
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage(
            'Hash type requires value of type array or null, Doctrine\Common\Collections\ArrayCollection given',
        );
        $type->convertToDatabaseValue(new ArrayCollection());
    }

    public function testHashDoesntAcceptScalar(): void
    {
        $type = new HashType();
        $this->expectException(MongoDBException::class);
        $this->expectExceptionMessage('Hash type requires value of type array or null, scalar given');
        $type->convertToDatabaseValue(true);
    }
}
