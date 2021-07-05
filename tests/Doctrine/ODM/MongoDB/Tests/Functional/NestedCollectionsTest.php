<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Phonebook;
use Documents\Phonenumber;

use function get_class;

class NestedCollectionsTest extends BaseTest
{
    /**
     * @dataProvider provideStrategy
     */
    public function testStrategy($field): void
    {
        $doc         = new DocWithNestedCollections();
        $privateBook = new Phonebook('Private');
        $privateBook->addPhonenumber(new Phonenumber('12345678'));
        $doc->{$field}[] = $privateBook;
        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getRepository(get_class($doc))->find($doc->id);
        $this->assertCount(1, $doc->{$field});
        $privateBook = $doc->{$field}[0];
        $this->assertEquals('Private', $privateBook->getTitle());
        $this->assertCount(1, $privateBook->getPhonenumbers());
        $this->assertEquals('12345678', $privateBook->getPhonenumbers()->get(0)->getPhonenumber());

        $privateBook->addPhonenumber(new Phonenumber('87654321'));
        $publicBook = new Phonebook('Public');
        $publicBook->addPhonenumber(new Phonenumber('10203040'));
        $doc->{$field}[] = $publicBook;
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getRepository(get_class($doc))->find($doc->id);
        $this->assertCount(2, $doc->{$field});
        $privateBook = $doc->{$field}[0];
        $this->assertEquals('Private', $privateBook->getTitle());
        $this->assertCount(2, $privateBook->getPhonenumbers());
        $this->assertEquals('12345678', $privateBook->getPhonenumbers()->get(0)->getPhonenumber());
        $this->assertEquals('87654321', $privateBook->getPhonenumbers()->get(1)->getPhonenumber());
        $publicBook = $doc->{$field}[1];
        $this->assertCount(1, $publicBook->getPhonenumbers());
        $this->assertEquals('10203040', $publicBook->getPhonenumbers()->get(0)->getPhonenumber());

        $privateBook->getPhonenumbers()->clear();
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->getRepository(get_class($doc))->find($doc->id);
        $this->assertCount(2, $doc->{$field});
        $privateBook = $doc->{$field}[0];
        $this->assertEquals('Private', $privateBook->getTitle());
        $this->assertCount(0, $privateBook->getPhonenumbers());
        $publicBook = $doc->{$field}[1];
        $this->assertCount(1, $publicBook->getPhonenumbers());
        $this->assertEquals('10203040', $publicBook->getPhonenumbers()->get(0)->getPhonenumber());
    }

    public function provideStrategy(): array
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

/**
 * @ODM\Document
 */
class DocWithNestedCollections
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(strategy="atomicSet", targetDocument=Documents\Phonebook::class) */
    public $atomicSet;

    /** @ODM\EmbedMany(strategy="atomicSetArray", targetDocument=Documents\Phonebook::class) */
    public $atomicSetArray;

    /** @ODM\EmbedMany(strategy="set", targetDocument=Documents\Phonebook::class) */
    public $set;

    /** @ODM\EmbedMany(strategy="setArray", targetDocument=Documents\Phonebook::class) */
    public $setArray;

    /** @ODM\EmbedMany(strategy="pushAll", targetDocument=Documents\Phonebook::class) */
    public $pushAll;

    /** @ODM\EmbedMany(strategy="addToSet", targetDocument=Documents\Phonebook::class) */
    public $addToSet;
}
