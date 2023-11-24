<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Card;
use Documents\Suit;
use Error;
use Jean85\PrettyVersions;
use MongoDB\BSON\ObjectId;
use ValueError;

use function preg_quote;
use function sprintf;
use function version_compare;

class EnumTest extends BaseTestCase
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

    public function testArrayOfEnums(): void
    {
        $persistenceVersion = PrettyVersions::getVersion('doctrine/persistence')->getPrettyVersion();
        if (version_compare('3.2.0', $persistenceVersion, '>')) {
            self::markTestSkipped('Support for array of enums was introduced in doctrine/persistence 3.2.0');
        }

        $doc        = new Card();
        $doc->suits = ['foo' => Suit::Clubs, 'bar' => Suit::Diamonds];

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $saved = $this->dm->find(Card::class, $doc->id);
        self::assertInstanceOf(Card::class, $saved);
        self::assertSame(['foo' => Suit::Clubs, 'bar' => Suit::Diamonds], $saved->suits);
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
}
