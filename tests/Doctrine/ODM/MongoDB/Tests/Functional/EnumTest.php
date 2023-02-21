<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Documents81\Card;
use Documents81\Suit;
use Error;
use MongoDB\BSON\ObjectId;
use ValueError;

use function preg_quote;
use function sprintf;

/** @requires PHP >= 8.1 */
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
        self::assertInstanceOf(Card::class, $saved);
        self::assertSame($doc->id, $saved->id);
        self::assertSame(Suit::Clubs, $saved->suit);
        self::assertNull($saved->nullableSuit);
    }

    public function testLoadingInvalidBackingValueThrowsError(): void
    {
        $document = [
            '_id' => new ObjectId(),
            'suit' => 'ABC',
        ];

        $this->dm->getDocumentCollection(Card::class)->insertOne($document);

        $this->expectException(ValueError::class);
        $this->expectExceptionMessageMatches(sprintf('/^"ABC" is not a valid backing value for enum "?%s"?$/', preg_quote(Suit::class)));
        $this->dm->getRepository(Card::class)->findOneBy([]);
    }

    public function testQueryWithMappedField(): void
    {
        $qb = $this->dm->createQueryBuilder(Card::class)
            ->field('suit')->equals(Suit::Spades)
            ->field('nullableSuit')->in([Suit::Hearts, Suit::Diamonds]);

        self::assertSame([
            'suit' => 'S',
            'nullableSuit' => [
                '$in' => ['H', 'D'],
            ],
        ], $qb->getQuery()->debug('query'));
    }

    public function testQueryWithMappedFieldAndEnumValue(): void
    {
        $qb = $this->dm->createQueryBuilder(Card::class)
            ->field('suit')->equals('S') // Suit::Spades->value
            ->field('nullableSuit')->in(['H', 'D']);

        self::assertSame([
            'suit' => 'S',
            'nullableSuit' => [
                '$in' => ['H', 'D'],
            ],
        ], $qb->getQuery()->debug('query'));
    }

    public function testQueryWithNotMappedField(): void
    {
        $qb = $this->dm->createQueryBuilder(Card::class)
            ->field('nonExisting')->equals(Suit::Clubs)
            ->field('nonExistingArray')->equals([Suit::Clubs, Suit::Hearts]);

        self::assertSame(['nonExisting' => 'C', 'nonExistingArray' => ['C', 'H']], $qb->getQuery()->debug('query'));
    }

    public function testQueryWithMappedNonEnumFieldIsPassedToTypeDirectly(): void
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage(sprintf('Object of class %s could not be converted to string', Suit::class));

        $qb = $this->dm->createQueryBuilder(Card::class)->field('_id')->equals(Suit::Clubs);

        self::assertSame(['_id' => 'C'], $qb->getQuery()->debug('query'));
    }

    protected function createMetadataDriverImpl(): MappingDriver
    {
        return AttributeDriver::create(__DIR__ . '/../../../Documents');
    }
}
