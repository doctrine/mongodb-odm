<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class IndexesTest extends BaseTest
{
    private function uniqueTest($class)
    {
        $this->dm->getSchemaManager()->ensureDocumentIndexes($class);

        $test = new $class();
        $test->username = 'jwage';
        $test->email = 'jonwage@gmail.com';
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = new $class();
        $test->username = 'jwage';
        $test->email = 'jonathan.wage@sensio.com';
        $this->dm->persist($test);
        $this->dm->flush();

        $test = new $class();
        $test->username = 'jwage';
        $test->email = 'jonathan.wage@sensio.com';
        $this->dm->persist($test);
        $this->dm->flush();
    }

    public function testEmbeddedIndexes()
    {
        $class = $this->dm->getClassMetadata(DocumentWithEmbeddedIndexes::class);
        $sm = $this->dm->getSchemaManager();
        $indexes = $sm->getDocumentIndexes($class->name);

        $this->assertTrue(isset($indexes[0]['keys']['embedded.name']));
        $this->assertEquals(1, $indexes[0]['keys']['embedded.name']);

        $this->assertTrue(isset($indexes[1]['keys']['embedded.embeddedMany.name']));
        $this->assertEquals(1, $indexes[1]['keys']['embedded.embeddedMany.name']);

        $this->assertTrue(isset($indexes[2]['keys']['embedded_secondary.name']));
        $this->assertEquals(1, $indexes[2]['keys']['embedded_secondary.name']);

        $this->assertTrue(isset($indexes[3]['keys']['embedded_secondary.embeddedMany.name']));
        $this->assertEquals(1, $indexes[3]['keys']['embedded_secondary.embeddedMany.name']);
    }

    public function testDiscriminatedEmbeddedIndexes()
    {
        $class = $this->dm->getClassMetadata(DocumentWithIndexInDiscriminatedEmbeds::class);
        $sm = $this->dm->getSchemaManager();
        $indexes = $sm->getDocumentIndexes($class->name);

        $this->assertTrue(isset($indexes[0]['keys']['embedded.name']));
        $this->assertEquals(1, $indexes[0]['keys']['embedded.name']);

        $this->assertTrue(isset($indexes[1]['keys']['embedded.embeddedMany.name']));
        $this->assertEquals(1, $indexes[1]['keys']['embedded.embeddedMany.name']);

        $this->assertTrue(isset($indexes[2]['keys']['embedded.value']));
        $this->assertEquals(1, $indexes[2]['keys']['embedded.value']);
    }

    public function testDiscriminatorIndexes()
    {
        $class = $this->dm->getClassMetadata(DocumentWithDiscriminatorIndex::class);
        $sm = $this->dm->getSchemaManager();
        $indexes = $sm->getDocumentIndexes($class->name);

        $this->assertTrue(isset($indexes[0]['keys']['type']));
        $this->assertEquals(1, $indexes[0]['keys']['type']);
    }

    public function testIndexDefinitions()
    {
        $class = $this->dm->getClassMetadata(UniqueOnFieldTest::class);
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);

        $class = $this->dm->getClassMetadata(UniqueOnDocumentTest::class);
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);

        $class = $this->dm->getClassMetadata(IndexesOnDocumentTest::class);
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);

        $class = $this->dm->getClassMetadata(PartialIndexOnDocumentTest::class);
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['partialFilterExpression']));
        $this->assertSame(['counter' => ['$gt' => 5]], $indexes[0]['options']['partialFilterExpression']);

        $class = $this->dm->getClassMetadata(UniqueSparseOnFieldTest::class);
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);
        $this->assertTrue(isset($indexes[0]['options']['sparse']));
        $this->assertEquals(true, $indexes[0]['options']['sparse']);

        $class = $this->dm->getClassMetadata(UniqueSparseOnDocumentTest::class);
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);
        $this->assertTrue(isset($indexes[0]['options']['sparse']));
        $this->assertEquals(true, $indexes[0]['options']['sparse']);

        $class = $this->dm->getClassMetadata(SparseIndexesOnDocumentTest::class);
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);
        $this->assertTrue(isset($indexes[0]['options']['sparse']));
        $this->assertEquals(true, $indexes[0]['options']['sparse']);

        $class = $this->dm->getClassMetadata(MultipleFieldsUniqueIndexTest::class);
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['keys']['email']));
        $this->assertEquals(1, $indexes[0]['keys']['email']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);

        $class = $this->dm->getClassMetadata(MultipleFieldsUniqueSparseIndexTest::class);
        $indexes = $class->getIndexes();
        $this->assertTrue(isset($indexes[0]['keys']['username']));
        $this->assertEquals(1, $indexes[0]['keys']['username']);
        $this->assertTrue(isset($indexes[0]['keys']['email']));
        $this->assertEquals(1, $indexes[0]['keys']['email']);
        $this->assertTrue(isset($indexes[0]['options']['unique']));
        $this->assertEquals(true, $indexes[0]['options']['unique']);
        $this->assertTrue(isset($indexes[0]['options']['sparse']));
        $this->assertEquals(true, $indexes[0]['options']['sparse']);

        $class = $this->dm->getClassMetadata(MultipleFieldIndexes::class);
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
     * @expectedException \MongoDB\Driver\Exception\BulkWriteException
     * @expectedExceptionMessage duplicate key error collection
     */
    public function testUniqueIndexOnField()
    {
        $this->uniqueTest(UniqueOnFieldTest::class);
    }

    /**
     * @expectedException \MongoDB\Driver\Exception\BulkWriteException
     * @expectedExceptionMessage duplicate key error collection
     */
    public function testUniqueIndexOnDocument()
    {
        $this->uniqueTest(UniqueOnDocumentTest::class);
    }

    /**
     * @expectedException \MongoDB\Driver\Exception\BulkWriteException
     * @expectedExceptionMessage duplicate key error collection
     */
    public function testIndexesOnDocument()
    {
        $this->uniqueTest(IndexesOnDocumentTest::class);
    }

    /**
     * @expectedException \MongoDB\Driver\Exception\BulkWriteException
     * @expectedExceptionMessage duplicate key error collection
     */
    public function testMultipleFieldsUniqueIndexOnDocument()
    {
        $this->uniqueTest(MultipleFieldsUniqueIndexTest::class);
    }

    /**
     * @expectedException \MongoDB\Driver\Exception\BulkWriteException
     * @expectedExceptionMessage duplicate key error collection
     */
    public function testMultipleFieldIndexes()
    {
        $this->uniqueTest(MultipleFieldIndexes::class);
    }

    public function testPartialIndexCreation()
    {
        $this->requireMongoDB32('This test is not applicable to server versions < 3.2.0');

        $className = PartialIndexOnDocumentTest::class;
        $this->dm->getSchemaManager()->ensureDocumentIndexes($className);

        $indexes = $this->dm->getSchemaManager()->getDocumentIndexes($className);
        $this->assertNotEmpty($indexes[0]['options']['partialFilterExpression']);
        $this->assertSame(['counter' => ['$gt' => 5]], $indexes[0]['options']['partialFilterExpression']);
        $this->assertTrue($indexes[0]['options']['unique']);
    }
}

/** @ODM\Document */
class UniqueOnFieldTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") @ODM\UniqueIndex() */
    public $username;

    /** @ODM\Field(type="string") */
    public $email;
}

/** @ODM\Document @ODM\UniqueIndex(keys={"username"="asc"}) */
class UniqueOnDocumentTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;

    /** @ODM\Field(type="string") */
    public $email;
}

/** @ODM\Document @ODM\Indexes(@ODM\UniqueIndex(keys={"username"="asc"})) */
class IndexesOnDocumentTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;

    /** @ODM\Field(type="string") */
    public $email;
}

/** @ODM\Document @ODM\Indexes(@ODM\UniqueIndex(keys={"username"="asc"},partialFilterExpression={"counter"={"$gt"=5}})) */
class PartialIndexOnDocumentTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;

    /** @ODM\Field(type="string") */
    public $email;

    /** @ODM\Field(type="integer") */
    public $counter;
}

/** @ODM\Document @ODM\UniqueIndex(keys={"username"="asc", "email"="asc"}) */
class MultipleFieldsUniqueIndexTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;

    /** @ODM\Field(type="string") */
    public $email;
}

/** @ODM\Document */
class UniqueSparseOnFieldTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") @ODM\UniqueIndex(sparse=true) */
    public $username;

    /** @ODM\Field(type="string") */
    public $email;
}

/** @ODM\Document @ODM\UniqueIndex(keys={"username"="asc"}, options={"sparse"=true}) */
class UniqueSparseOnDocumentTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;

    /** @ODM\Field(type="string") */
    public $email;
}

/** @ODM\Document @ODM\Indexes(@ODM\UniqueIndex(keys={"username"="asc"}, options={"sparse"=true})) */
class SparseIndexesOnDocumentTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;

    /** @ODM\Field(type="string") */
    public $email;
}

/** @ODM\Document @ODM\UniqueIndex(keys={"username"="asc", "email"="asc"}, options={"sparse"=true}) */
class MultipleFieldsUniqueSparseIndexTest
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $username;

    /** @ODM\Field(type="string") */
    public $email;
}

/** @ODM\Document */
class MultipleFieldIndexes
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") @ODM\UniqueIndex(name="test") */
    public $username;

    /** @ODM\Field(type="string") @ODM\Index(unique=true) */
    public $email;
}

/** @ODM\Document */
class DocumentWithEmbeddedIndexes
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedOne(targetDocument=EmbeddedDocumentWithIndexes::class) */
    public $embedded;

    /** @ODM\EmbedOne(targetDocument=EmbeddedDocumentWithIndexes::class) */
    public $embedded_secondary;
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
    /** @ODM\Field(type="string") @ODM\Index */
    public $name;

    /** @ODM\EmbedMany(targetDocument=EmbeddedManyDocumentWithIndexes::class) */
    public $embeddedMany;
}

/** @ODM\EmbeddedDocument */
class EmbeddedManyDocumentWithIndexes
{
    /** @ODM\Field(type="string") @ODM\Index */
    public $name;
}

/** @ODM\EmbeddedDocument */
class YetAnotherEmbeddedDocumentWithIndex
{
    /** @ODM\Field(type="string") @ODM\Index */
    public $value;
}

/** @ODM\Document */
class DocumentWithIndexInDiscriminatedEmbeds
{
    /** @ODM\Id */
    public $id;

    /**
     * @ODM\EmbedOne(
     *  discriminatorMap={
     *   "d1"=EmbeddedDocumentWithIndexes::class,
     *   "d2"=YetAnotherEmbeddedDocumentWithIndex::class,
     * })
     */
    public $embedded;
}
