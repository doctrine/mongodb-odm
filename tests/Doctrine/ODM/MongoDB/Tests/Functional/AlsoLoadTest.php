<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

use function explode;

class AlsoLoadTest extends BaseTest
{
    public function testPropertyAlsoLoadDoesNotInterfereWithBasicHydration(): void
    {
        $document = [
            'foo' => 'foo',
            'bar' => 'bar',
            'baz' => null,
            'zip' => 'zip',
        ];

        $this->dm->getDocumentCollection(AlsoLoadDocument::class)->insertOne($document);

        $document = $this->dm->getRepository(AlsoLoadDocument::class)->findOneBy([]);

        self::assertEquals('foo', $document->foo, '"foo" gets its own value and ignores "bar" and "zip"');
        self::assertEquals('bar', $document->bar, '"bar" is hydrated normally');
        self::assertNull($document->baz, '"baz" gets its own null value and ignores "zip" and "bar"');
    }

    public function testPropertyAlsoLoadMayOverwriteDefaultPropertyValue(): void
    {
        $document = ['zip' => 'zip'];

        $this->dm->getDocumentCollection(AlsoLoadDocument::class)->insertOne($document);

        $document = $this->dm->getRepository(AlsoLoadDocument::class)->findOneBy([]);

        self::assertEquals('zip', $document->zap, '"zap" gets value from "zip", overwriting its default value');
        self::assertEquals('zip', $document->zip, '"zip" is hydrated normally');
    }

    public function testPropertyAlsoLoadShortCircuitsAfterFirstFieldIsFound(): void
    {
        $document = [
            'bar' => null,
            'zip' => 'zip',
        ];

        $this->dm->getDocumentCollection(AlsoLoadDocument::class)->insertOne($document);

        $document = $this->dm->getRepository(AlsoLoadDocument::class)->findOneBy([]);

        self::assertEquals(null, $document->foo, '"foo" gets null value from "bar" and ignores "zip"');
        self::assertEquals('zip', $document->baz, '"baz" gets value from "zip" and ignores "bar"');
        self::assertEquals(null, $document->bar, '"bar" is hydrated normally');
        self::assertEquals('zip', $document->zip, '"zip" is hydrated normally');
    }

    public function testPropertyAlsoLoadChecksMultipleFields(): void
    {
        $document = ['zip' => 'zip'];

        $this->dm->getDocumentCollection(AlsoLoadDocument::class)->insertOne($document);

        $document = $this->dm->getRepository(AlsoLoadDocument::class)->findOneBy([]);

        self::assertEquals('zip', $document->foo, '"foo" gets value from "zip" since "bar" was missing');
        self::assertNull($document->bar, '"bar" is not hydrated');
        self::assertEquals('zip', $document->zip, '"zip" is hydrated normally');
    }

    public function testPropertyAlsoLoadBeatsMethodAlsoLoad(): void
    {
        $document = [
            'testNew' => 'testNew',
            'testOld' => 'testOld',
        ];

        $this->dm->getDocumentCollection(AlsoLoadDocument::class)->insertOne($document);

        $document = $this->dm->getRepository(AlsoLoadDocument::class)->findOneBy([]);

        self::assertEquals('testNew', $document->test, '"test" gets value from "testNew"');
        self::assertEquals('testNew', $document->testNew, '"testNew" is hydrated normally');
        self::assertEquals('testOld', $document->testOld, '"testOld" is hydrated normally');
    }

    public function testMethodAlsoLoadDoesNotInterfereWithBasicHydration(): void
    {
        $document = [
            'firstName' => 'Jonathan',
            'name' => 'Kris Wallsmith',
        ];

        $this->dm->getDocumentCollection(AlsoLoadDocument::class)->insertOne($document);

        $document = $this->dm->getRepository(AlsoLoadDocument::class)->findOneBy([]);

        self::assertEquals('Jonathan', $document->firstName, '"firstName" gets value from exploded "name" but is overwritten with normal hydration');
        self::assertEquals('Wallsmith', $document->lastName, '"lastName" gets value from exploded "name"');
        self::assertEquals('Kris Wallsmith', $document->name, '"name" is hydrated normally');
    }

    public function testMethodAlsoLoadMayOverwriteDefaultPropertyValue(): void
    {
        $document = ['testOld' => null];

        $this->dm->getDocumentCollection(AlsoLoadDocument::class)->insertOne($document);

        $document = $this->dm->getRepository(AlsoLoadDocument::class)->findOneBy([]);

        self::assertNull($document->test, '"test" gets value from "testOld", overwriting its default value');
        self::assertNull($document->testOld, '"testOld" is hydrated normally"');
    }

    public function testMethodAlsoLoadShortCircuitsAfterFirstFieldIsFound(): void
    {
        $document = [
            'name' => 'Jonathan Wage',
            'fullName' => 'Kris Wallsmith',
            'testOld' => 'testOld',
            'testOlder' => 'testOlder',
        ];

        $this->dm->getDocumentCollection(AlsoLoadDocument::class)->insertOne($document);

        $document = $this->dm->getRepository(AlsoLoadDocument::class)->findOneBy([]);

        self::assertEquals('Jonathan Wage', $document->name, '"name" is hydrated normally');
        self::assertEquals('Kris Wallsmith', $document->fullName, '"fullName" is hydrated normally');
        self::assertEquals('Jonathan', $document->firstName, '"firstName" gets value from exploded "name" and ignores "fullName"');
        self::assertEquals('Wage', $document->lastName, '"lastName" gets value from exploded "name" and ignores "fullName"');

        self::assertEquals('testOld', $document->test, '"test" gets value from "testOld" and ignores "testOlder"');
        self::assertEquals('testOld', $document->testOld, '"testOld" is hydrated normally');
        self::assertEquals('testOlder', $document->testOlder, '"testOlder" is hydrated normally');
    }

    public function testMethodAlsoLoadChecksMultipleFields(): void
    {
        $document = [
            'fullName' => 'Kris Wallsmith',
            'testOlder' => 'testOlder',
        ];

        $this->dm->getDocumentCollection(AlsoLoadDocument::class)->insertOne($document);

        $document = $this->dm->getRepository(AlsoLoadDocument::class)->findOneBy([]);

        self::assertNull($document->name, '"name" is not hydrated');
        self::assertEquals('Kris Wallsmith', $document->fullName, '"fullName" is hydrated normally');
        self::assertEquals('Kris', $document->firstName, '"firstName" gets value from exploded "fullName" since "name" was missing');
        self::assertEquals('Wallsmith', $document->lastName, '"lastName" gets value from exploded "fullName" since "name" was missing');

        self::assertEquals('testOlder', $document->test, '"test" gets value from "testOlder" since "testOld" was missing');
        self::assertNull($document->testOld, '"testOld" is not hydrated');
        self::assertEquals('testOlder', $document->testOlder, '"testOlder" is hydrated normally');
    }

    public function testNotSaved(): void
    {
        $document            = new AlsoLoadDocument();
        $document->baz       = 'baz';
        $document->firstName = 'Jonathan';
        $document->lastName  = 'Wage';
        $document->name      = 'Kris Wallsmith';

        $this->dm->persist($document);
        $this->dm->flush();

        $document = $this->dm->getDocumentCollection(AlsoLoadDocument::class)->findOne();

        self::assertEquals('Jonathan', $document['firstName'], '"firstName" is hydrated normally');
        self::assertEquals('Wage', $document['lastName'], '"lastName" is hydrated normally');
        self::assertArrayNotHasKey('name', $document, '"name" was not saved');
        self::assertArrayNotHasKey('baz', $document, '"baz" was not saved');
    }

    public function testMethodAlsoLoadParentInheritance(): void
    {
        $document = [
            'buzz' => 'buzz',
            'test' => 'test',
            'testOld' => 'testOld',
            'testOlder' => 'testOlder',
        ];

        $this->dm->getDocumentCollection(AlsoLoadChild::class)->insertOne($document);

        $document = $this->dm->getRepository(AlsoLoadChild::class)->findOneBy([]);

        self::assertEquals('buzz', $document->fizz, '"fizz" gets value from "buzz"');
        self::assertEquals('test', $document->test, '"test" is hydrated normally, since "testOldest" was missing and parent method was overridden');
    }

    public function testMethodAlsoLoadGrandparentInheritance(): void
    {
        $document = [
            'buzz' => 'buzz',
            'testReallyOldest' => 'testReallyOldest',
        ];

        $this->dm->getDocumentCollection(AlsoLoadGrandchild::class)->insertOne($document);

        $document = $this->dm->getRepository(AlsoLoadGrandchild::class)->findOneBy([]);

        self::assertEquals('buzz', $document->fizz, '"fizz" gets value from "buzz"');
        self::assertEquals('testReallyOldest', $document->test, '"test" gets value from "testReallyOldest"');
    }
}

/** @ODM\Document */
class AlsoLoadDocument
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     * @ODM\AlsoLoad({"bar", "zip"})
     *
     * @var string|null
     */
    public $foo;

    /**
     * @ODM\Field(notSaved=true)
     * @ODM\AlsoLoad({"zip", "bar"})
     *
     * @var string|null
     */
    public $baz;

    /**
     * @ODM\Field(notSaved=true)
     *
     * @var string|null
     */
    public $bar;

    /**
     * @ODM\Field(notSaved=true)
     *
     * @var string|null
     */
    public $zip;

    /**
     * @ODM\Field(type="string")
     * @ODM\AlsoLoad("zip")
     *
     * @var string|null
     */
    public $zap = 'zap';

    /**
     * @ODM\Field(notSaved=true)
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\Field(notSaved=true)
     *
     * @var string|null
     */
    public $fullName;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $firstName;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $lastName;

    /**
     * @ODM\Field(type="string")
     * @ODM\AlsoLoad("testNew")
     *
     * @var string|null
     */
    public $test = 'test';

    /**
     * @ODM\Field(notSaved=true)
     *
     * @var string|null
     */
    public $testNew;

    /**
     * @ODM\Field(notSaved=true)
     *
     * @var string|null
     */
    public $testOld;

    /**
     * @ODM\Field(notSaved=true)
     *
     * @var string|null
     */
    public $testOlder;

    /** @ODM\AlsoLoad({"name", "fullName"}) */
    public function populateFirstAndLastName(string $name): void
    {
        [$this->firstName, $this->lastName] = explode(' ', $name);
    }

    /** @ODM\AlsoLoad ({"testOld", "testOlder"}) */
    public function populateTest(?string $test): void
    {
        $this->test = $test;
    }
}

/** @ODM\Document */
class AlsoLoadChild extends AlsoLoadDocument
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $fizz;

    /** @ODM\AlsoLoad("buzz") */
    public function populateFizz(string $fizz): void
    {
        $this->fizz = $fizz;
    }

    /** @ODM\AlsoLoad ("testOldest") */
    public function populateTest(?string $test): void
    {
        $this->test = $test;
    }
}

/** @ODM\Document */
class AlsoLoadGrandchild extends AlsoLoadChild
{
    /** @ODM\AlsoLoad ("testReallyOldest") */
    public function populateTest(?string $test): void
    {
        $this->test = $test;
    }
}
