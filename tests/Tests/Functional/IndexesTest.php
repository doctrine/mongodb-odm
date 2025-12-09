<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\Driver\Exception\BulkWriteException;

class IndexesTest extends BaseTestCase
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

    public function testGeoIndexCreation(): void
    {
        $className = GeoIndexDocument::class;
        $this->dm->getSchemaManager()->ensureDocumentIndexes(GeoIndexDocument::class);

        $indexes = $this->dm->getSchemaManager()->getDocumentIndexes($className);
        self::assertSame(['coordinatesWith2DIndex' => '2d'], $indexes[0]['keys']);
        self::assertSame(['coordinatesWithSphereIndex' => '2dsphere'], $indexes[1]['keys']);
    }
}

#[ODM\Document]
class UniqueOnFieldTest
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    #[ODM\UniqueIndex]
    public $username;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $email;
}

#[ODM\Document]
#[ODM\UniqueIndex(keys: ['username' => 'asc'])]
class UniqueOnDocumentTest
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $username;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $email;
}

#[ODM\Document]
#[ODM\UniqueIndex(keys: ['username' => 'asc'])]
class IndexesOnDocumentTest
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $username;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $email;
}

#[ODM\Document]
#[ODM\UniqueIndex(keys: ['username' => 'asc'], partialFilterExpression: ['counter' => ['$gt' => 5]])]
class PartialIndexOnDocumentTest
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $username;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $email;

    /** @var int|null */
    #[ODM\Field(type: 'int')]
    public $counter;
}

#[ODM\Document]
#[ODM\UniqueIndex(keys: ['username' => 'asc', 'email' => 'asc'])]
class MultipleFieldsUniqueIndexTest
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $username;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $email;
}

#[ODM\Document]
class UniqueSparseOnFieldTest
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    #[ODM\UniqueIndex(sparse: true)]
    public $username;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $email;
}

#[ODM\Document]
#[ODM\UniqueIndex(keys: ['username' => 'asc'], options: ['sparse' => true])]
class UniqueSparseOnDocumentTest
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $username;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $email;
}

#[ODM\Document]
#[ODM\UniqueIndex(keys: ['username' => 'asc'], options: ['sparse' => true])]
class SparseIndexesOnDocumentTest
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $username;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $email;
}

#[ODM\Document]
#[ODM\UniqueIndex(keys: ['username' => 'asc', 'email' => 'asc'], options: ['sparse' => true])]
class MultipleFieldsUniqueSparseIndexTest
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $username;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $email;
}

#[ODM\Document]
class MultipleFieldIndexes
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    #[ODM\UniqueIndex(name: 'test')]
    public $username;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    #[ODM\Index(unique: true)]
    public $email;
}

#[ODM\Document]
class DocumentWithEmbeddedIndexes
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var EmbeddedDocumentWithIndexes|null */
    #[ODM\EmbedOne(targetDocument: EmbeddedDocumentWithIndexes::class)]
    public $embedded;

    /** @var EmbeddedDocumentWithIndexes|null */
    #[ODM\EmbedOne(targetDocument: EmbeddedDocumentWithIndexes::class)]
    public $embeddedSecondary;
}

#[ODM\Document]
#[ODM\DiscriminatorField('type')]
#[ODM\Index(keys: ['type' => 'asc'])]
class DocumentWithDiscriminatorIndex
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
}

#[ODM\Document]
#[ODM\Index(keys: ['name' => 'asc'])]
#[ODM\Index(keys: ['name' => 'desc'])]
#[ODM\UniqueIndex(keys: ['name' => 'asc'], options: ['sparse' => true])]
class DocumentWithMultipleIndexAnnotations
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $name;
}

#[ODM\EmbeddedDocument]
class EmbeddedDocumentWithIndexes
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    #[ODM\Index]
    public $name;

    /** @var Collection<int, EmbeddedManyDocumentWithIndexes> */
    #[ODM\EmbedMany(targetDocument: EmbeddedManyDocumentWithIndexes::class)]
    public $embeddedMany;
}

#[ODM\EmbeddedDocument]
class EmbeddedManyDocumentWithIndexes
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    #[ODM\Index]
    public $name;
}

#[ODM\EmbeddedDocument]
class YetAnotherEmbeddedDocumentWithIndex
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    #[ODM\Index]
    public $value;
}

#[ODM\Document]
class DocumentWithIndexInDiscriminatedEmbeds
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var EmbeddedDocumentWithIndexes|YetAnotherEmbeddedDocumentWithIndex|null */
    #[ODM\EmbedOne(discriminatorMap: ['d1' => EmbeddedDocumentWithIndexes::class, 'd2' => YetAnotherEmbeddedDocumentWithIndex::class])]
    public $embedded;
}

#[ODM\Document]
#[ODM\Index(keys: ['coordinatesWith2DIndex' => '2d'])]
#[ODM\Index(keys: ['coordinatesWithSphereIndex' => '2dsphere'])]
class GeoIndexDocument
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var array<float> */
    #[ODM\Field(type: 'hash')]
    public $coordinatesWith2DIndex;

    /** @var array<float> */
    #[ODM\Field(type: 'hash')]
    public $coordinatesWithSphereIndex;
}
