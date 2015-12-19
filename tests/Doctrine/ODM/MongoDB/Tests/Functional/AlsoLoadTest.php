<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Functional\AlsoLoad;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class AlsoLoadTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testPropertyAlsoLoadDoesNotInterfereWithBasicHydration()
    {
        $document = array(
            'foo' => 'foo',
            'bar' => 'bar',
            'baz' => null,
            'zip' => 'zip',
        );

        $this->dm->getDocumentCollection(__NAMESPACE__ . '\AlsoLoadDocument')->insert($document);

        $document = $this->dm->getRepository(__NAMESPACE__ . '\AlsoLoadDocument')->findOneBy(array());

        $this->assertEquals('foo', $document->foo, '"foo" gets its own value and ignores "bar" and "zip"');
        $this->assertEquals('bar', $document->bar, '"bar" is hydrated normally');
        $this->assertNull($document->baz, '"baz" gets its own null value and ignores "zip" and "bar"');
    }

    public function testPropertyAlsoLoadMayOverwriteDefaultPropertyValue()
    {
        $document = array('zip' => 'zip');

        $this->dm->getDocumentCollection(__NAMESPACE__ . '\AlsoLoadDocument')->insert($document);

        $document = $this->dm->getRepository(__NAMESPACE__ . '\AlsoLoadDocument')->findOneBy(array());

        $this->assertEquals('zip', $document->zap, '"zap" gets value from "zip", overwriting its default value');
        $this->assertEquals('zip', $document->zip, '"zip" is hydrated normally');
    }

    public function testPropertyAlsoLoadShortCircuitsAfterFirstFieldIsFound()
    {
        $document = array(
            'bar' => null,
            'zip' => 'zip',
        );

        $this->dm->getDocumentCollection(__NAMESPACE__ . '\AlsoLoadDocument')->insert($document);

        $document = $this->dm->getRepository(__NAMESPACE__ . '\AlsoLoadDocument')->findOneBy(array());

        $this->assertEquals(null, $document->foo, '"foo" gets null value from "bar" and ignores "zip"');
        $this->assertEquals('zip', $document->baz, '"baz" gets value from "zip" and ignores "bar"');
        $this->assertEquals(null, $document->bar, '"bar" is hydrated normally');
        $this->assertEquals('zip', $document->zip, '"zip" is hydrated normally');
    }

    public function testPropertyAlsoLoadChecksMultipleFields()
    {
        $document = array('zip' => 'zip');

        $this->dm->getDocumentCollection(__NAMESPACE__ . '\AlsoLoadDocument')->insert($document);

        $document = $this->dm->getRepository(__NAMESPACE__ . '\AlsoLoadDocument')->findOneBy(array());

        $this->assertEquals('zip', $document->foo, '"foo" gets value from "zip" since "bar" was missing');
        $this->assertNull($document->bar, '"bar" is not hydrated');
        $this->assertEquals('zip', $document->zip, '"zip" is hydrated normally');
    }

    public function testPropertyAlsoLoadBeatsMethodAlsoLoad()
    {
        $document = array(
            'testNew' => 'testNew',
            'testOld' => 'testOld',
        );

        $this->dm->getDocumentCollection(__NAMESPACE__ . '\AlsoLoadDocument')->insert($document);

        $document = $this->dm->getRepository(__NAMESPACE__ . '\AlsoLoadDocument')->findOneBy(array());

        $this->assertEquals('testNew', $document->test, '"test" gets value from "testNew"');
        $this->assertEquals('testNew', $document->testNew, '"testNew" is hydrated normally');
        $this->assertEquals('testOld', $document->testOld, '"testOld" is hydrated normally');
    }

    public function testMethodAlsoLoadDoesNotInterfereWithBasicHydration()
    {
        $document = array(
            'firstName' => 'Jonathan',
            'name' => 'Kris Wallsmith',
        );

        $this->dm->getDocumentCollection(__NAMESPACE__ . '\AlsoLoadDocument')->insert($document);

        $document = $this->dm->getRepository(__NAMESPACE__ . '\AlsoLoadDocument')->findOneBy(array());

        $this->assertEquals('Jonathan', $document->firstName, '"firstName" gets value from exploded "name" but is overwritten with normal hydration');
        $this->assertEquals('Wallsmith', $document->lastName, '"lastName" gets value from exploded "name"');
        $this->assertEquals('Kris Wallsmith', $document->name, '"name" is hydrated normally');
    }

    public function testMethodAlsoLoadMayOverwriteDefaultPropertyValue()
    {
        $document = array('testOld' => null);

        $this->dm->getDocumentCollection(__NAMESPACE__ . '\AlsoLoadDocument')->insert($document);

        $document = $this->dm->getRepository(__NAMESPACE__ . '\AlsoLoadDocument')->findOneBy(array());

        $this->assertNull($document->test, '"test" gets value from "testOld", overwriting its default value');
        $this->assertNull($document->testOld, '"testOld" is hydrated normally"');
    }

    public function testMethodAlsoLoadShortCircuitsAfterFirstFieldIsFound()
    {
        $document = array(
            'name' => 'Jonathan Wage',
            'fullName' => 'Kris Wallsmith',
            'testOld' => 'testOld',
            'testOlder' => 'testOlder',
        );

        $this->dm->getDocumentCollection(__NAMESPACE__ . '\AlsoLoadDocument')->insert($document);

        $document = $this->dm->getRepository(__NAMESPACE__ . '\AlsoLoadDocument')->findOneBy(array());

        $this->assertEquals('Jonathan Wage', $document->name, '"name" is hydrated normally');
        $this->assertEquals('Kris Wallsmith', $document->fullName, '"fullName" is hydrated normally');
        $this->assertEquals('Jonathan', $document->firstName, '"firstName" gets value from exploded "name" and ignores "fullName"');
        $this->assertEquals('Wage', $document->lastName, '"lastName" gets value from exploded "name" and ignores "fullName"');

        $this->assertEquals('testOld', $document->test, '"test" gets value from "testOld" and ignores "testOlder"');
        $this->assertEquals('testOld', $document->testOld, '"testOld" is hydrated normally');
        $this->assertEquals('testOlder', $document->testOlder, '"testOlder" is hydrated normally');
    }

    public function testMethodAlsoLoadChecksMultipleFields()
    {
        $document = array(
            'fullName' => 'Kris Wallsmith',
            'testOlder' => 'testOlder',
        );

        $this->dm->getDocumentCollection(__NAMESPACE__ . '\AlsoLoadDocument')->insert($document);

        $document = $this->dm->getRepository(__NAMESPACE__ . '\AlsoLoadDocument')->findOneBy(array());

        $this->assertNull($document->name, '"name" is not hydrated');
        $this->assertEquals('Kris Wallsmith', $document->fullName, '"fullName" is hydrated normally');
        $this->assertEquals('Kris', $document->firstName, '"firstName" gets value from exploded "fullName" since "name" was missing');
        $this->assertEquals('Wallsmith', $document->lastName, '"lastName" gets value from exploded "fullName" since "name" was missing');

        $this->assertEquals('testOlder', $document->test, '"test" gets value from "testOlder" since "testOld" was missing');
        $this->assertNull($document->testOld, '"testOld" is not hydrated');
        $this->assertEquals('testOlder', $document->testOlder, '"testOlder" is hydrated normally');
    }

    public function testNotSaved()
    {
        $document = new AlsoLoadDocument();
        $document->baz = 'baz';
        $document->firstName = 'Jonathan';
        $document->lastName = 'Wage';
        $document->name = 'Kris Wallsmith';

        $this->dm->persist($document);
        $this->dm->flush();

        $document = $this->dm->getDocumentCollection(__NAMESPACE__ . '\AlsoLoadDocument')->findOne();

        $this->assertEquals('Jonathan', $document['firstName'], '"firstName" is hydrated normally');
        $this->assertEquals('Wage', $document['lastName'], '"lastName" is hydrated normally');
        $this->assertArrayNotHasKey('name', $document, '"name" was not saved');
        $this->assertArrayNotHasKey('baz', $document, '"baz" was not saved');
    }

    public function testMethodAlsoLoadParentInheritance()
    {
        $document = array(
            'buzz' => 'buzz',
            'test' => 'test',
            'testOld' => 'testOld',
            'testOlder' => 'testOlder',
        );

        $this->dm->getDocumentCollection(__NAMESPACE__ . '\AlsoLoadChild')->insert($document);

        $document = $this->dm->getRepository(__NAMESPACE__ . '\AlsoLoadChild')->findOneBy(array());

        $this->assertEquals('buzz', $document->fizz, '"fizz" gets value from "buzz"');
        $this->assertEquals('test', $document->test, '"test" is hydrated normally, since "testOldest" was missing and parent method was overridden');
    }

    public function testMethodAlsoLoadGrandparentInheritance()
    {
        $document = array(
            'buzz' => 'buzz',
            'testReallyOldest' => 'testReallyOldest',
        );

        $this->dm->getDocumentCollection(__NAMESPACE__ . '\AlsoLoadGrandchild')->insert($document);

        $document = $this->dm->getRepository(__NAMESPACE__ . '\AlsoLoadGrandchild')->findOneBy(array());

        $this->assertEquals('buzz', $document->fizz, '"fizz" gets value from "buzz"');
        $this->assertEquals('testReallyOldest', $document->test, '"test" gets value from "testReallyOldest"');
    }
}

/** @ODM\Document */
class AlsoLoadDocument
{
    /** @ODM\Id */
    public $id;

    /**
     * @ODM\Field(type="string")
     * @ODM\AlsoLoad({"bar", "zip"})
     */
    public $foo;

    /**
     * @ODM\NotSaved
     * @ODM\AlsoLoad({"zip", "bar"})
     */
    public $baz;

    /** @ODM\NotSaved */
    public $bar;

    /** @ODM\NotSaved */
    public $zip;

    /**
     * @ODM\Field(type="string")
     * @ODM\AlsoLoad("zip")
     */
    public $zap = 'zap';

    /** @ODM\NotSaved */
    public $name;

    /** @ODM\NotSaved */
    public $fullName;

    /** @ODM\Field(type="string") */
    public $firstName;

    /** @ODM\Field(type="string") */
    public $lastName;

    /**
     * @ODM\Field(type="string")
     * @ODM\AlsoLoad("testNew")
     */
    public $test = 'test';

    /** @ODM\NotSaved */
    public $testNew;

    /** @ODM\NotSaved */
    public $testOld;

    /** @ODM\NotSaved */
    public $testOlder;

    /** @ODM\AlsoLoad({"name", "fullName"}) */
    public function populateFirstAndLastName($name)
    {
        list($this->firstName, $this->lastName) = explode(' ', $name);
    }

    /** @ODM\AlsoLoad({"testOld", "testOlder"}) */
    public function populateTest($test)
    {
        $this->test = $test;
    }
}

/** @ODM\Document */
class AlsoLoadChild extends AlsoLoadDocument
{
    /** @ODM\Field(type="string") */
    public $fizz;

    /** @ODM\AlsoLoad("buzz") */
    public function populateFizz($fizz)
    {
        $this->fizz = $fizz;
    }

    /** @ODM\AlsoLoad("testOldest") */
    public function populateTest($test)
    {
        $this->test = $test;
    }
}

/** @ODM\Document */
class AlsoLoadGrandchild extends AlsoLoadChild
{
    /** @ODM\AlsoLoad("testReallyOldest") */
    public function populateTest($test)
    {
        $this->test = $test;
    }
}
