<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

class UniqueIndexTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private function uniqueTest($class)
    {
        $class = __NAMESPACE__.'\\'.$class;
        $this->dm->getSchemaManager()->ensureDocumentIndexes($class);

        $test = new $class();
        $test->username = 'jwage';
        $test->email = 'jonwage@gmail.com';
        $this->dm->persist($test);
        $this->dm->flush(array('safe' => true));
        $this->dm->clear();

        $test = new $class();
        $test->username = 'jwage';
        $test->email = 'jonathan.wage@sensio.com';
        $this->dm->persist($test);
        $this->dm->flush(array('safe' => true));

        $test = new $class();
        $test->username = 'jwage';
        $test->email = 'jonathan.wage@sensio.com';
        $this->dm->persist($test);
        $this->dm->flush(array('safe' => true));
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

        $class = $this->dm->getClassMetadata(__NAMESPACE__.'\MultipleFieldsUniqueIndexTest');
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['keys']['email']));
        $this->assertEquals(1, $indexes[0]['keys']['email']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);

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

/** @Document */
class UniqueOnFieldTest
{
    /** @Id */
    public $id;

    /** @String @UniqueIndex(safe=true) */
    public $username;

    /** @String */
    public $email;
}

/** @Document @UniqueIndex(keys={"username"="asc"}) */
class UniqueOnDocumentTest
{
    /** @Id */
    public $id;

    /** @String */
    public $username;

    /** @String */
    public $email;
}

/** @Document @Indexes(@UniqueIndex(keys={"username"="asc"})) */
class IndexesOnDocumentTest
{
    /** @Id */
    public $id;

    /** @String */
    public $username;

    /** @String */
    public $email;
}

/** @Document @UniqueIndex(keys={"username"="asc", "email"="asc"}) */
class MultipleFieldsUniqueIndexTest
{
    /** @Id */
    public $id;

    /** @String */
    public $username;

    /** @String */
    public $email;
}

/** @Document */
class MultipleFieldIndexes
{
    /** @Id */
    public $id;

    /** @String @UniqueIndex(name="test") */
    public $username;

    /** @String @Index(unique=true) */
    public $email;
}

/** @Document */
class DocumentWithEmbeddedIndexes
{
    /** @Id */
    public $id;

    /** @String */
    public $name;

    /** @EmbedOne(targetDocument="EmbeddedDocumentWithIndexes") */
    public $embedded;
}

/** @EmbeddedDocument */
class EmbeddedDocumentWithIndexes
{
    /** @String @Index */
    public $name;

    /** @EmbedMany(targetDocument="EmbeddedManyDocumentWithIndexes") */
    public $embeddedMany;
}

/** @EmbeddedDocument */
class EmbeddedManyDocumentWithIndexes
{
    /** @String @Index */
    public $name;
}