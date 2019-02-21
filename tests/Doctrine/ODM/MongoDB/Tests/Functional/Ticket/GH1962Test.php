<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH1962Test extends BaseTest
{
    public function testDiscriminatorMaps()
    {
        $metadata = $this->dm->getClassMetadata(GH1962Superclass::class);
        self::assertCount(3, $metadata->discriminatorMap);

        $metadata = $this->dm->getClassMetadata(GH1962BarSuperclass::class);
        self::assertCount(2, $metadata->discriminatorMap);
    }

    public function testFetchingDiscriminatedDocuments()
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
    /** @ODM\Id */
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
