<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class IndexesTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function uniqueTest($class)
    {
        $class = __NAMESPACE__.'\\'.$class;
        $this->dm->getSchemaManager()->ensureDocumentIndexes($class);

        $test = new $class();
        $test->username = 'jwage';
        $test->email = 'jonwage@gmail.com';
        $this->dm->persist($test);
        $this->dm->flush(null, array('safe' => true));
        $this->dm->clear();

        $test = new $class();
        $test->username = 'jwage';
        $test->email = 'jonathan.wage@sensio.com';
        $this->dm->persist($test);
        $this->dm->flush(null, array('safe' => true));

        $test = new $class();
        $test->username = 'jwage';
        $test->email = 'jonathan.wage@sensio.com';
        $this->dm->persist($test);
        $this->dm->flush(null, array('safe' => true));
    }

    public function testEmbeddedIndexes()
    {
        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\DocumentWithEmbeddedIndexes');
        $sm = $this->dm->getSchemaManager();
        $indexes = $sm->getDocumentIndexes($class->name);

        $this->assertTrue(isset($indexes[0]['keys']['embedded.name']));
        $this->assertEquals(1, $indexes[0]['keys']['embedded.name']);

        $this->assertTrue(isset($indexes[1]['keys']['embedded.embeddedMany.name']));
        $this->assertEquals(1, $indexes[1]['keys']['embedded.embeddedMany.name']);
    }

    public function testDiscriminatorIndexes()
    {
        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\DocumentWithDiscriminatorIndex');
        $sm = $this->dm->getSchemaManager();
        $indexes = $sm->getDocumentIndexes($class->name);

        $this->assertTrue(isset($indexes[0]['keys']['type']));
        $this->assertEquals(1, $indexes[0]['keys']['type']);
    }

    public function testIndexDefinitions()
    {
        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\UniqueOnFieldTest');
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);
        $this->assertEquals(true, $indexes[0]['options']['safe']);

        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\UniqueOnDocumentTest');
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);

        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\IndexesOnDocumentTest');
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);

        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\UniqueSparseOnFieldTest');
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);
        $this->assertEquals(true, $indexes[0]['options']['safe']);
        $this->assertTrue(isset($indexes[0]['options']['sparse']));
        $this->assertEquals(true, $indexes[0]['options']['sparse']);

        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\UniqueSparseOnDocumentTest');
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);
        $this->assertTrue(isset($indexes[0]['options']['sparse']));
        $this->assertEquals(true, $indexes[0]['options']['sparse']);

        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\SparseIndexesOnDocumentTest');
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);
        $this->assertTrue(isset($indexes[0]['options']['sparse']));
        $this->assertEquals(true, $indexes[0]['options']['sparse']);

        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\MultipleFieldsUniqueIndexTest');
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['keys']['email']));
        $this->assertEquals(1, $indexes[0]['keys']['email']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);

        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\MultipleFieldsUniqueSparseIndexTest');
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['keys']['email']));
        $this->assertEquals(1, $indexes[0]['keys']['email']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);
        $this->assertTrue(isset($indexes[0]['options']['sparse']));
        $this->assertEquals(true, $indexes[0]['options']['sparse']);

        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\MultipleFieldIndexes');
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);

        $this->assertTrue(isset($indexes[1]['keys']['email']));
        $this->assertEquals(1, $indexes[1]['keys']['email']);
        $this->assertTrue(isset($indexes[1]['options']['unique']));
        $this->assertEquals(true, $indexes[1]['options']['unique']);
        $this->assertEquals('test', $indexes[0]['options']['name']);
    }

    /**
     * @expectedException MongoCursorException
     */
    public function testUniqueIndexOnField()
    {
        $this->uniqueTest('UniqueOnFieldTest');
    }

    /**
     * @expectedException MongoCursorException
     */
    public function testUniqueIndexOnDocument()
    {
        $this->uniqueTest('UniqueOnDocumentTest');
    }

    /**
     * @expectedException MongoCursorException
     */
    public function testIndexesOnDocument()
    {
        $this->uniqueTest('IndexesOnDocumentTest');
    }

    /**
     * @expectedException MongoCursorException
     */
    public function testMultipleFieldsUniqueIndexOnDocument()
    {
        $this->uniqueTest('MultipleFieldsUniqueIndexTest');
    }

    /**
     * @expectedException MongoCursorException
     */
    public function testMultipleFieldIndexes()
    {
        $this->uniqueTest('MultipleFieldIndexes');
    }
}

/** @ODM\Document */
class UniqueOnFieldTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String @ODM\UniqueIndex(safe=true) */
    public $username;

    /** @ODM\String */
    public $email;
}

/** @ODM\Document @ODM\UniqueIndex(keys={"username"="asc"}) */
class UniqueOnDocumentTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $username;

    /** @ODM\String */
    public $email;
}

/** @ODM\Document @ODM\Indexes(@ODM\UniqueIndex(keys={"username"="asc"})) */
class IndexesOnDocumentTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $username;

    /** @ODM\String */
    public $email;
}

/** @ODM\Document @ODM\UniqueIndex(keys={"username"="asc", "email"="asc"}) */
class MultipleFieldsUniqueIndexTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $username;

    /** @ODM\String */
    public $email;
}

/** @ODM\Document */
class UniqueSparseOnFieldTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String @ODM\UniqueIndex(safe=true, sparse=true) */
    public $username;

    /** @ODM\String */
    public $email;
}

/** @ODM\Document @ODM\UniqueIndex(keys={"username"="asc"}, options={"sparse"=true}) */
class UniqueSparseOnDocumentTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $username;

    /** @ODM\String */
    public $email;
}

/** @ODM\Document @ODM\Indexes(@ODM\UniqueIndex(keys={"username"="asc"}, options={"sparse"=true})) */
class SparseIndexesOnDocumentTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $username;

    /** @ODM\String */
    public $email;
}

/** @ODM\Document @ODM\UniqueIndex(keys={"username"="asc", "email"="asc"}, options={"sparse"=true}) */
class MultipleFieldsUniqueSparseIndexTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $username;

    /** @ODM\String */
    public $email;
}

/** @ODM\Document */
class MultipleFieldIndexes
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String @ODM\UniqueIndex(name="test") */
    public $username;

    /** @ODM\String @ODM\Index(unique=true) */
    public $email;
}

/** @ODM\Document */
class DocumentWithEmbeddedIndexes
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    /** @ODM\EmbedOne(targetDocument="EmbeddedDocumentWithIndexes") */
    public $embedded;
}

/**
 * @ODM\Document
 * @ODM\DiscriminatorField("type")
 * @ODM\Index(keys={"type"="asc"})
 */
class DocumentWithDiscriminatorIndex
{
    /** @ODM\Id */
    public $id;
}

/** @ODM\EmbeddedDocument */
class EmbeddedDocumentWithIndexes
{
    /** @ODM\String @ODM\Index */
    public $name;

    /** @ODM\EmbedMany(targetDocument="EmbeddedManyDocumentWithIndexes") */
    public $embeddedMany;
}

/** @ODM\EmbeddedDocument */
class EmbeddedManyDocumentWithIndexes
{
    /** @ODM\String @ODM\Index */
    public $name;
}