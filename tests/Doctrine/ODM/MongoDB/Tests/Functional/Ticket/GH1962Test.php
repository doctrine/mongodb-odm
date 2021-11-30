<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1962Test extends BaseTest
{
    public function testDiscriminatorMaps(): void
    {
        $metadata = $this->dm->getClassMetadata(GH1962Superclass::class);
        self::assertCount(3, $metadata->discriminatorMap);

        $metadata = $this->dm->getClassMetadata(GH1962BarSuperclass::class);
        self::assertCount(2, $metadata->discriminatorMap);
    }

    public function testFetchingDiscriminatedDocuments(): void
    {
        $foo = new GH1962FooDocument();
        $bar = new GH1962BarDocument();
        $baz = new GH1962BazDocument();

        $this->dm->persist($foo);
        $this->dm->persist($bar);
        $this->dm->persist($baz);

        $this->dm->flush();
        $this->dm->clear();

        self::assertCount(3, $this->dm->getRepository(GH1962Superclass::class)->findAll());
        self::assertCount(2, $this->dm->getRepository(GH1962BarSuperclass::class)->findAll());

        self::assertCount(1, $this->dm->getRepository(GH1962FooDocument::class)->findAll());
        self::assertCount(1, $this->dm->getRepository(GH1962BarDocument::class)->findAll());
        self::assertCount(1, $this->dm->getRepository(GH1962BazDocument::class)->findAll());
    }

    public function testFetchingDiscriminatedDocumentsWithoutDiscriminatorMap(): void
    {
        $foo = new GH1962FooDocumentWithoutDiscriminatorMap();
        $bar = new GH1962BarDocumentWithoutDiscriminatorMap();
        $baz = new GH1962BazDocumentWithoutDiscriminatorMap();

        $this->dm->persist($foo);
        $this->dm->persist($bar);
        $this->dm->persist($baz);

        $this->dm->flush();
        $this->dm->clear();

        // Since the extending superclass does not know about its child classes,
        // it will yield the same results as querying for the parent class
        // Without discriminator maps, only leafs in the inheritance tree will
        // use a discriminator value in the query
        self::assertCount(3, $this->dm->getRepository(GH1962SuperclassWithoutDiscriminatorMap::class)->findAll());
        self::assertCount(3, $this->dm->getRepository(GH1962BarSuperclassWithoutDiscriminatorMap::class)->findAll());

        self::assertCount(3, $this->dm->getRepository(GH1962FooDocumentWithoutDiscriminatorMap::class)->findAll());
        self::assertCount(3, $this->dm->getRepository(GH1962BarDocumentWithoutDiscriminatorMap::class)->findAll());
        self::assertCount(1, $this->dm->getRepository(GH1962BazDocumentWithoutDiscriminatorMap::class)->findAll());
    }
}

/**
 * @ODM\MappedSuperclass()
 * @ODM\DiscriminatorField("type")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorMap({
 *     "foo"=GH1962FooDocument::class,
 *     "bar"=GH1962BarDocument::class,
 *     "baz"=GH1962BazDocument::class
 * })
 */
class GH1962Superclass
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;
}

/** @ODM\Document */
class GH1962FooDocument extends GH1962Superclass
{
}

/**
 * @ODM\MappedSuperclass()
 * @ODM\DiscriminatorMap({
 *     "bar"=GH1962BarDocument::class,
 *     "baz"=GH1962BazDocument::class
 * })
 */
class GH1962BarSuperclass extends GH1962Superclass
{
}

/** @ODM\Document */
class GH1962BarDocument extends GH1962BarSuperclass
{
}

/** @ODM\Document */
class GH1962BazDocument extends GH1962BarSuperclass
{
}
/**
 * @ODM\MappedSuperclass()
 * @ODM\DiscriminatorField("type")
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 */
class GH1962SuperclassWithoutDiscriminatorMap
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;
}

/** @ODM\Document */
class GH1962FooDocumentWithoutDiscriminatorMap extends GH1962SuperclassWithoutDiscriminatorMap
{
}

/** @ODM\MappedSuperclass() */
class GH1962BarSuperclassWithoutDiscriminatorMap extends GH1962SuperclassWithoutDiscriminatorMap
{
}

/** @ODM\Document */
class GH1962BarDocumentWithoutDiscriminatorMap extends GH1962BarSuperclassWithoutDiscriminatorMap
{
}

/**
 * @ODM\Document
 * @ODM\DiscriminatorValue(GH1962BazDocumentWithoutDiscriminatorMap::class)
 */
class GH1962BazDocumentWithoutDiscriminatorMap extends GH1962BarSuperclassWithoutDiscriminatorMap
{
}
