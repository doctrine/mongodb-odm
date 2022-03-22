<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Documents81\Card;
use Documents81\Suit;
use MongoDB\BSON\ObjectId;
use ValueError;

use function assert;
use function sprintf;

/**
 * @requires PHP 8.1
 */
class EnumTest extends BaseTest
{
    public function testPersistNew(): void
    {
        $doc       = new Card();
        $doc->suit = Suit::Clubs;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $saved = $this->dm->find(Card::class, $doc->id);
        assert($saved instanceof Card);
        $this->assertSame($doc->id, $saved->id);
        $this->assertSame(Suit::Clubs, $saved->suit);
        $this->assertNull($saved->nullableSuit);
    }

    public function testLoadingInvalidBackingValueThrowsError(): void
    {
        $document = [
            '_id' => new ObjectId(),
            'suit' => 'ABC',
        ];

        $this->dm->getDocumentCollection(Card::class)->insertOne($document);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage(sprintf('"ABC" is not a valid backing value for enum "%s"', Suit::class));
        $this->dm->getRepository(Card::class)->findOneBy([]);
    }

    protected function createMetadataDriverImpl(): MappingDriver
    {
        return AttributeDriver::create(__DIR__ . '/../../../Documents');
    }
}
