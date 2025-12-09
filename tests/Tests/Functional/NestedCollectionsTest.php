<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\Phonebook;
use Documents\Phonenumber;
use PHPUnit\Framework\Attributes\DataProvider;

class NestedCollectionsTest extends BaseTestCase
{
    #[DataProvider('provideStrategy')]
    public function testStrategy(string $field): void
    {
        $doc         = new DocWithNestedCollections();
        $privateBook = new Phonebook('Private');
        $privateBook->addPhonenumber(new Phonenumber('12345678'));
        $doc->{$field}[] = $privateBook;
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getRepository($doc::class)->find($doc->id);
        self::assertCount(1, $doc->{$field});
        $privateBook = $doc->{$field}[0];
        self::assertEquals('Private', $privateBook->getTitle());
        self::assertCount(1, $privateBook->getPhonenumbers());
        self::assertEquals('12345678', $privateBook->getPhonenumbers()->get(0)->getPhonenumber());

        $privateBook->addPhonenumber(new Phonenumber('87654321'));
        $publicBook = new Phonebook('Public');
        $publicBook->addPhonenumber(new Phonenumber('10203040'));
        $doc->{$field}[] = $publicBook;
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getRepository($doc::class)->find($doc->id);
        self::assertCount(2, $doc->{$field});
        $privateBook = $doc->{$field}[0];
        self::assertEquals('Private', $privateBook->getTitle());
        self::assertCount(2, $privateBook->getPhonenumbers());
        self::assertEquals('12345678', $privateBook->getPhonenumbers()->get(0)->getPhonenumber());
        self::assertEquals('87654321', $privateBook->getPhonenumbers()->get(1)->getPhonenumber());
        $publicBook = $doc->{$field}[1];
        self::assertCount(1, $publicBook->getPhonenumbers());
        self::assertEquals('10203040', $publicBook->getPhonenumbers()->get(0)->getPhonenumber());

        $privateBook->getPhonenumbers()->clear();
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getRepository($doc::class)->find($doc->id);
        self::assertCount(2, $doc->{$field});
        $privateBook = $doc->{$field}[0];
        self::assertEquals('Private', $privateBook->getTitle());
        self::assertEmpty($privateBook->getPhonenumbers());
        $publicBook = $doc->{$field}[1];
        self::assertCount(1, $publicBook->getPhonenumbers());
        self::assertEquals('10203040', $publicBook->getPhonenumbers()->get(0)->getPhonenumber());
    }

    public static function provideStrategy(): array
    {
        return [
            [ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET],
            [ClassMetadata::STORAGE_STRATEGY_ATOMIC_SET_ARRAY],
            [ClassMetadata::STORAGE_STRATEGY_SET],
            [ClassMetadata::STORAGE_STRATEGY_SET_ARRAY],
            [ClassMetadata::STORAGE_STRATEGY_PUSH_ALL],
            [ClassMetadata::STORAGE_STRATEGY_ADD_TO_SET],
        ];
    }
}

#[ODM\Document]
class DocWithNestedCollections
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var Collection<int, Phonebook> */
    #[ODM\EmbedMany(strategy: 'atomicSet', targetDocument: Phonebook::class)]
    public $atomicSet;

    /** @var Collection<int, Phonebook> */
    #[ODM\EmbedMany(strategy: 'atomicSetArray', targetDocument: Phonebook::class)]
    public $atomicSetArray;

    /** @var Collection<int, Phonebook> */
    #[ODM\EmbedMany(strategy: 'set', targetDocument: Phonebook::class)]
    public $set;

    /** @var Collection<int, Phonebook> */
    #[ODM\EmbedMany(strategy: 'setArray', targetDocument: Phonebook::class)]
    public $setArray;

    /** @var Collection<int, Phonebook> */
    #[ODM\EmbedMany(strategy: 'pushAll', targetDocument: Phonebook::class)]
    public $pushAll;

    /** @var Collection<int, Phonebook> */
    #[ODM\EmbedMany(strategy: 'addToSet', targetDocument: Phonebook::class)]
    public $addToSet;
}
