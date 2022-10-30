<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\Driver\Exception\BulkWriteException;

class IndexesTest extends BaseTest
{
    /** @param class-string $class */
    private function uniqueTest(string $class): void
    {
        $this->dm->getSchemaManager()->ensureDocumentIndexes($class);

        $test           = new $class();
        $test->username = 'jwage';
        $test->email    = 'jonwage@gmail.com';
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test           = new $class();
        $test->username = 'jwage';
        $test->email    = 'jonathan.wage@sensio.com';
        $this->dm->persist($test);
        $this->dm->flush();

        $test           = new $class();
        $test->username = 'jwage';
        $test->email    = 'jonathan.wage@sensio.com';
        $this->dm->persist($test);
        $this->dm->flush();
    }

    public function testEmbeddedIndexes(): void
    {
        $class   = $this->dm->getClassMetadata(DocumentWithEmbeddedIndexes::class);
        $sm      = $this->dm->getSchemaManager();
        $indexes = $sm->getDocumentIndexes($class->name);

        self::assertTrue(isset($indexes[0]['keys']['embedded.name']));
        self::assertEquals(1, $indexes[0]['keys']['embedded.name']);

        self::assertTrue(isset($indexes[1]['keys']['embedded.embeddedMany.name']));
        self::assertEquals(1, $indexes[1]['keys']['embedded.embeddedMany.name']);

        self::assertTrue(isset($indexes[2]['keys']['embeddedSecondary.name']));
        self::assertEquals(1, $indexes[2]['keys']['embeddedSecondary.name']);

        self::assertTrue(isset($indexes[3]['keys']['embeddedSecondary.embeddedMany.name']));
        self::assertEquals(1, $indexes[3]['keys']['embeddedSecondary.embeddedMany.name']);
    }

    public function testDiscriminatedEmbeddedIndexes(): void
    {
        $class   = $this->dm->getClassMetadata(DocumentWithIndexInDiscriminatedEmbeds::class);
        $sm      = $this->dm->getSchemaManager();
        $indexes = $sm->getDocumentIndexes($class->name);

        self::assertTrue(isset($indexes[0]['keys']['embedded.name']));
        self::assertEquals(1, $indexes[0]['keys']['embedded.name']);

        self::assertTrue(isset($indexes[1]['keys']['embedded.embeddedMany.name']));
        self::assertEquals(1, $indexes[1]['keys']['embedded.embeddedMany.name']);

        self::assertTrue(isset($indexes[2]['keys']['embedded.value']));
        self::assertEquals(1, $indexes[2]['keys']['embedded.value']);
    }

    public function testDiscriminatorIndexes(): void
    {
        $class   = $this->dm->getClassMetadata(DocumentWithDiscriminatorIndex::class);
        $sm      = $this->dm->getSchemaManager();
        $indexes = $sm->getDocumentIndexes($class->name);

        self::assertTrue(isset($indexes[0]['keys']['type']));
        self::assertEquals(1, $indexes[0]['keys']['type']);
    }

    public function testMultipleIndexAnnotations(): void
    {
        $class   = $this->dm->getClassMetadata(DocumentWithMultipleIndexAnnotations::class);
        $sm      = $this->dm->getSchemaManager();
        $indexes = $sm->getDocumentIndexes($class->name);

        self::assertCount(3, $indexes);

        self::assertTrue(isset($indexes[0]['keys']['name']));
        self::assertEquals(1, $indexes[0]['keys']['name']);

        self::assertTrue(isset($indexes[1]['keys']['name']));
        self::assertEquals(-1, $indexes[1]['keys']['name']);

        self::assertTrue(isset($indexes[2]['keys']['name']));
        self::assertEquals(1, $indexes[2]['keys']['name']);
        self::assertTrue(isset($indexes[2]['options']['unique']));
        self::assertEquals(true, $indexes[2]['options']['unique']);
        self::assertTrue(isset($indexes[2]['options']['sparse']));
        self::assertEquals(true, $indexes[2]['options']['sparse']);
    }

    public function testIndexDefinitions(): void
    {
        $class   = $this->dm->getClassMetadata(UniqueOnFieldTest::class);
        $indexes = $class->getIndexes();
        self::assertTrue(isset($indexes[0]['keys']['username']));
        self::assertEquals(1, $indexes[0]['keys']['username']);
        self::assertTrue(isset($indexes[0]['options']['unique']));
        self::assertEquals(true, $indexes[0]['options']['unique']);

        $class   = $this->dm->getClassMetadata(UniqueOnDocumentTest::class);
        $indexes = $class->getIndexes();
        self::assertTrue(isset($indexes[0]['keys']['username']));
        self::assertEquals(1, $indexes[0]['keys']['username']);
        self::assertTrue(isset($indexes[0]['options']['unique']));
        self::assertEquals(true, $indexes[0]['options']['unique']);

        $class   = $this->dm->getClassMetadata(IndexesOnDocumentTest::class);
        $indexes = $class->getIndexes();
        self::assertTrue(isset($indexes[0]['keys']['username']));
        self::assertEquals(1, $indexes[0]['keys']['username']);
        self::assertTrue(isset($indexes[0]['options']['unique']));
        self::assertEquals(true, $indexes[0]['options']['unique']);

        $class   = $this->dm->getClassMetadata(PartialIndexOnDocumentTest::class);
        $indexes = $class->getIndexes();
        self::assertTrue(isset($indexes[0]['keys']['username']));
        self::assertEquals(1, $indexes[0]['keys']['username']);
        self::assertTrue(isset($indexes[0]['options']['partialFilterExpression']));
        self::assertSame(['counter' => ['$gt' => 5]], $indexes[0]['options']['partialFilterExpression']);

        $class   = $this->dm->getClassMetadata(UniqueSparseOnFieldTest::class);
        $indexes = $class->getIndexes();
        self::assertTrue(isset($indexes[0]['keys']['username']));
        self::assertEquals(1, $indexes[0]['keys']['username']);
        self::assertTrue(isset($indexes[0]['options']['unique']));
        self::assertEquals(true, $indexes[0]['options']['unique']);
        self::assertTrue(isset($indexes[0]['options']['sparse']));
        self::assertEquals(true, $indexes[0]['options']['sparse']);

        $class   = $this->dm->getClassMetadata(UniqueSparseOnDocumentTest::class);
        $indexes = $class->getIndexes();
        self::assertTrue(isset($indexes[0]['keys']['username']));
        self::assertEquals(1, $indexes[0]['keys']['username']);
        self::assertTrue(isset($indexes[0]['options']['unique']));
        self::assertEquals(true, $indexes[0]['options']['unique']);
        self::assertTrue(isset($indexes[0]['options']['sparse']));
        self::assertEquals(true, $indexes[0]['options']['sparse']);

        $class   = $this->dm->getClassMetadata(SparseIndexesOnDocumentTest::class);
        $indexes = $class->getIndexes();
        self::assertTrue(isset($indexes[0]['keys']['username']));
        self::assertEquals(1, $indexes[0]['keys']['username']);
        self::assertTrue(isset($indexes[0]['options']['unique']));
        self::assertEquals(true, $indexes[0]['options']['unique']);
        self::assertTrue(isset($indexes[0]['options']['sparse']));
        self::assertEquals(true, $indexes[0]['options']['sparse']);

        $class   = $this->dm->getClassMetadata(MultipleFieldsUniqueIndexTest::class);
        $indexes = $class->getIndexes();
        self::assertTrue(isset($indexes[0]['keys']['username']));
        self::assertEquals(1, $indexes[0]['keys']['username']);
        self::assertTrue(isset($indexes[0]['keys']['email']));
        self::assertEquals(1, $indexes[0]['keys']['email']);
        self::assertTrue(isset($indexes[0]['options']['unique']));
        self::assertEquals(true, $indexes[0]['options']['unique']);

        $class   = $this->dm->getClassMetadata(MultipleFieldsUniqueSparseIndexTest::class);
        $indexes = $class->getIndexes();
        self::assertTrue(isset($indexes[0]['keys']['username']));
        self::assertEquals(1, $indexes[0]['keys']['username']);
        self::assertTrue(isset($indexes[0]['keys']['email']));
        self::assertEquals(1, $indexes[0]['keys']['email']);
        self::assertTrue(isset($indexes[0]['options']['unique']));
        self::assertEquals(true, $indexes[0]['options']['unique']);
        self::assertTrue(isset($indexes[0]['options']['sparse']));
        self::assertEquals(true, $indexes[0]['options']['sparse']);

        $class   = $this->dm->getClassMetadata(MultipleFieldIndexes::class);
        $indexes = $class->getIndexes();
        self::assertTrue(isset($indexes[0]['keys']['username']));
        self::assertEquals(1, $indexes[0]['keys']['username']);
        self::assertTrue(isset($indexes[0]['options']['unique']));
        self::assertEquals(true, $indexes[0]['options']['unique']);

        self::assertTrue(isset($indexes[1]['keys']['email']));
        self::assertEquals(1, $indexes[1]['keys']['email']);
        self::assertTrue(isset($indexes[1]['options']['unique']));
        self::assertEquals(true, $indexes[1]['options']['unique']);
        self::assertEquals('test', $indexes[0]['options']['name']);
    }

    public function testUniqueIndexOnField(): void
    {
        $this->expectException(BulkWriteException::class);
        $this->expectExceptionMessage('duplicate key error');
        $this->uniqueTest(UniqueOnFieldTest::class);
    }

    public function testUniqueIndexOnDocument(): void
    {
        $this->expectException(BulkWriteException::class);
        $this->expectExceptionMessage('duplicate key error');
        $this->uniqueTest(UniqueOnDocumentTest::class);
    }

    public function testIndexesOnDocument(): void
    {
        $this->expectException(BulkWriteException::class);
        $this->expectExceptionMessage('duplicate key error');
        $this->uniqueTest(IndexesOnDocumentTest::class);
    }

    public function testMultipleFieldsUniqueIndexOnDocument(): void
    {
        $this->expectException(BulkWriteException::class);
        $this->expectExceptionMessage('duplicate key error');
        $this->uniqueTest(MultipleFieldsUniqueIndexTest::class);
    }

    public function testMultipleFieldIndexes(): void
    {
        $this->expectException(BulkWriteException::class);
        $this->expectExceptionMessage('duplicate key error');
        $this->uniqueTest(MultipleFieldIndexes::class);
    }

    public function testPartialIndexCreation(): void
    {
        $className = PartialIndexOnDocumentTest::class;
        $this->dm->getSchemaManager()->ensureDocumentIndexes($className);

        $indexes = $this->dm->getSchemaManager()->getDocumentIndexes($className);
        self::assertNotEmpty($indexes[0]['options']['partialFilterExpression']);
        self::assertSame(['counter' => ['$gt' => 5]], $indexes[0]['options']['partialFilterExpression']);
        self::assertTrue($indexes[0]['options']['unique']);
    }
}

/** @ODM\Document */
class UniqueOnFieldTest
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     * @ODM\UniqueIndex()
     *
     * @var string|null
     */
    public $username;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $email;
}

/** @ODM\Document @ODM\UniqueIndex(keys={"username"="asc"}) */
class UniqueOnDocumentTest
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $username;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $email;
}

/** @ODM\Document @ODM\Indexes(@ODM\UniqueIndex(keys={"username"="asc"})) */
class IndexesOnDocumentTest
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $username;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $email;
}

/** @ODM\Document @ODM\Indexes(@ODM\UniqueIndex(keys={"username"="asc"},partialFilterExpression={"counter"={"$gt"=5}})) */
class PartialIndexOnDocumentTest
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $username;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $email;

    /**
     * @ODM\Field(type="int")
     *
     * @var int|null
     */
    public $counter;
}

/** @ODM\Document @ODM\UniqueIndex(keys={"username"="asc", "email"="asc"}) */
class MultipleFieldsUniqueIndexTest
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $username;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $email;
}

/** @ODM\Document */
class UniqueSparseOnFieldTest
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     * @ODM\UniqueIndex(sparse=true)
     *
     * @var string|null
     */
    public $username;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $email;
}

/** @ODM\Document @ODM\UniqueIndex(keys={"username"="asc"}, options={"sparse"=true}) */
class UniqueSparseOnDocumentTest
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $username;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $email;
}

/** @ODM\Document @ODM\Indexes(@ODM\UniqueIndex(keys={"username"="asc"}, options={"sparse"=true})) */
class SparseIndexesOnDocumentTest
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $username;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $email;
}

/** @ODM\Document @ODM\UniqueIndex(keys={"username"="asc", "email"="asc"}, options={"sparse"=true}) */
class MultipleFieldsUniqueSparseIndexTest
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $username;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $email;
}

/** @ODM\Document */
class MultipleFieldIndexes
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     * @ODM\UniqueIndex(name="test")
     *
     * @var string|null
     */
    public $username;

    /**
     * @ODM\Field(type="string")
     * @ODM\Index(unique=true)
     *
     * @var string|null
     */
    public $email;
}

/** @ODM\Document */
class DocumentWithEmbeddedIndexes
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\EmbedOne(targetDocument=EmbeddedDocumentWithIndexes::class)
     *
     * @var EmbeddedDocumentWithIndexes|null
     */
    public $embedded;

    /**
     * @ODM\EmbedOne(targetDocument=EmbeddedDocumentWithIndexes::class)
     *
     * @var EmbeddedDocumentWithIndexes|null
     */
    public $embeddedSecondary;
}

/**
 * @ODM\Document
 * @ODM\DiscriminatorField("type")
 * @ODM\Index(keys={"type"="asc"})
 */
class DocumentWithDiscriminatorIndex
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;
}

/**
 * @ODM\Document
 * @ODM\Index(keys={"name"="asc"})
 * @ODM\Index(keys={"name"="desc"})
 * @ODM\UniqueIndex(keys={"name"="asc"}, options={"sparse"=true})
 */
class DocumentWithMultipleIndexAnnotations
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $name;
}

/** @ODM\EmbeddedDocument */
class EmbeddedDocumentWithIndexes
{
    /**
     * @ODM\Field(type="string")
     * @ODM\Index
     *
     * @var string|null
     */
    public $name;

    /**
     * @ODM\EmbedMany(targetDocument=EmbeddedManyDocumentWithIndexes::class)
     *
     * @var Collection<int, EmbeddedManyDocumentWithIndexes>
     */
    public $embeddedMany;
}

/** @ODM\EmbeddedDocument */
class EmbeddedManyDocumentWithIndexes
{
    /**
     * @ODM\Field(type="string")
     * @ODM\Index
     *
     * @var string|null
     */
    public $name;
}

/** @ODM\EmbeddedDocument */
class YetAnotherEmbeddedDocumentWithIndex
{
    /**
     * @ODM\Field(type="string")
     * @ODM\Index
     *
     * @var string|null
     */
    public $value;
}

/** @ODM\Document */
class DocumentWithIndexInDiscriminatedEmbeds
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\EmbedOne(
     *  discriminatorMap={
     *   "d1"=EmbeddedDocumentWithIndexes::class,
     *   "d2"=YetAnotherEmbeddedDocumentWithIndex::class,
     * })
     *
     * @var EmbeddedDocumentWithIndexes|YetAnotherEmbeddedDocumentWithIndex|null
     */
    public $embedded;
}
