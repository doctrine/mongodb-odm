<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Documents81\Card;
use Documents81\Suit;

use function assert;

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

    protected function createMetadataDriverImpl(): MappingDriver
    {
        return AttributeDriver::create(__DIR__ . '/../../../Documents');
    }
}
