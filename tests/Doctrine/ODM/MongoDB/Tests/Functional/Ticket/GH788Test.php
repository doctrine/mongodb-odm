<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use function get_class;

class GH788Test extends BaseTest
{
    public function testDocumentWithDiscriminatorMap()
    {
        $listed       = new GH788DocumentListed();
        $listed->name = 'listed';

        $unlisted       = new GH788DocumentUnlisted();
        $unlisted->name = 'unlisted';

        $this->dm->persist($listed);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($listed), $listed->id);
        $this->assertInstanceOf(GH788DocumentListed::class, $doc);
        $this->assertEquals('listed', $doc->name);

        $this->dm->persist($unlisted);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH788DocumentUnlisted::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }

    public function testEmbedManyWithExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $listed                 = new GH788ExternEmbedListed();
        $listed->name           = 'listed';
        $doc->externEmbedMany[] = $listed;

        $unlisted               = new GH788ExternEmbedUnlisted();
        $unlisted->name         = 'unlisted';
        $doc->externEmbedMany[] = $unlisted;

        $this->dm->persist($doc);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH788ExternEmbedUnlisted::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }

    public function testEmbedManyWithInlineDiscriminatorMap()
    {
        $doc = new GH788Document();

        $listed                 = new GH788InlineEmbedListed();
        $listed->name           = 'listed';
        $doc->inlineEmbedMany[] = $listed;

        $unlisted               = new GH788InlineEmbedUnlisted();
        $unlisted->name         = 'unlisted';
        $doc->inlineEmbedMany[] = $unlisted;

        $this->dm->persist($doc);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH788InlineEmbedUnlisted::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }

    public function testEmbedManyWithNoTargetAndExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $listed                   = new GH788ExternEmbedListed();
        $listed->name             = 'listed';
        $doc->noTargetEmbedMany[] = $listed;

        $unlisted                 = new GH788ExternEmbedUnlisted();
        $unlisted->name           = 'unlisted';
        $doc->noTargetEmbedMany[] = $unlisted;

        $this->dm->persist($doc);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH788ExternEmbedUnlisted::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }

    public function testEmbedOneWithExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $unlisted            = new GH788ExternEmbedUnlisted();
        $unlisted->name      = 'unlisted';
        $doc->externEmbedOne = $unlisted;

        $this->dm->persist($doc);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH788ExternEmbedUnlisted::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }

    public function testEmbedOneWithInlineDiscriminatorMap()
    {
        $doc = new GH788Document();

        $unlisted            = new GH788InlineEmbedUnlisted();
        $unlisted->name      = 'unlisted';
        $doc->inlineEmbedOne = $unlisted;

        $this->dm->persist($doc);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH788InlineEmbedUnlisted::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }

    public function testEmbedOneWithNoTargetAndExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $unlisted              = new GH788ExternEmbedUnlisted();
        $unlisted->name        = 'unlisted';
        $doc->noTargetEmbedOne = $unlisted;

        $this->dm->persist($doc);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH788ExternEmbedUnlisted::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }

    public function testRefManyWithExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $listed               = new GH788ExternRefListed();
        $listed->name         = 'listed';
        $doc->externRefMany[] = $listed;

        $unlisted             = new GH788ExternRefUnlisted();
        $unlisted->name       = 'unlisted';
        $doc->externRefMany[] = $unlisted;

        $this->dm->persist($doc);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH788ExternRefUnlisted::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }

    public function testRefManyWithInlineDiscriminatorMap()
    {
        $doc = new GH788Document();

        $listed               = new GH788InlineRefListed();
        $listed->name         = 'listed';
        $doc->inlineRefMany[] = $listed;

        $unlisted             = new GH788InlineRefUnlisted();
        $unlisted->name       = 'unlisted';
        $doc->inlineRefMany[] = $unlisted;

        $this->dm->persist($doc);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH788InlineRefUnlisted::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }

    public function testRefManyWithNoTargetAndExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $listed                 = new GH788ExternRefListed();
        $listed->name           = 'listed';
        $doc->noTargetRefMany[] = $listed;

        $unlisted               = new GH788ExternRefUnlisted();
        $unlisted->name         = 'unlisted';
        $doc->noTargetRefMany[] = $unlisted;

        $this->dm->persist($doc);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH788ExternRefUnlisted::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }

    public function testRefOneWithExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $unlisted          = new GH788ExternRefUnlisted();
        $unlisted->name    = 'unlisted';
        $doc->externRefOne = $unlisted;

        $this->dm->persist($doc);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH788ExternRefUnlisted::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }

    public function testRefOneWithInlineDiscriminatorMap()
    {
        $doc = new GH788Document();

        $unlisted          = new GH788InlineRefUnlisted();
        $unlisted->name    = 'unlisted';
        $doc->inlineRefOne = $unlisted;

        $this->dm->persist($doc);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH788InlineRefUnlisted::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }

    public function testRefOneWithNoTargetAndExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $unlisted            = new GH788ExternRefUnlisted();
        $unlisted->name      = 'unlisted';
        $doc->noTargetRefOne = $unlisted;

        $this->dm->persist($doc);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Document class "' . GH788ExternRefUnlisted::class . '" is unlisted in the discriminator map.');
        $this->dm->flush();
    }
}

/** @ODM\Document */
class GH788Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument=GH788ExternEmbedListed::class) */
    public $externEmbedMany;

    /** @ODM\EmbedOne(targetDocument=GH788ExternEmbedListed::class) */
    public $externEmbedOne;

    /** @ODM\ReferenceMany(targetDocument=GH788ExternRefListed::class, cascade="all") */
    public $externRefMany;

    /** @ODM\ReferenceOne(targetDocument=GH788ExternRefListed::class, cascade="all") */
    public $externRefOne;

    /**
     * @ODM\EmbedMany(
     *   discriminatorField="type",
     *   discriminatorMap={
     *     "b"="GH788InlineEmbedListed"
     *   }
     * )
     */
    public $inlineEmbedMany;

    /**
     * @ODM\EmbedOne(
     *   discriminatorField="type",
     *   discriminatorMap={
     *     "b"="GH788InlineEmbedListed"
     *   }
     * )
     */
    public $inlineEmbedOne;

    /**
     * @ODM\ReferenceMany(
     *   discriminatorField="type",
     *   discriminatorMap={
     *     "c"="GH788InlineRefListed"
     *   },
     *   cascade="all"
     * )
     */
    public $inlineRefMany;

    /**
     * @ODM\ReferenceOne(
     *   discriminatorField="type",
     *   discriminatorMap={
     *     "c"="GH788InlineRefListed"
     *   },
     *   cascade="all"
     * )
     */
    public $inlineRefOne;

    /** @ODM\EmbedMany */
    public $noTargetEmbedMany;

    /** @ODM\EmbedOne */
    public $noTargetEmbedOne;

    /** @ODM\ReferenceMany(cascade="all") */
    public $noTargetRefMany;

    /** @ODM\ReferenceOne(cascade="all") */
    public $noTargetRefOne;

    public function __construct()
    {
        $this->externEmbedMany   = new ArrayCollection();
        $this->externRefMany     = new ArrayCollection();
        $this->inlineEmbedMany   = new ArrayCollection();
        $this->inlineRefMany     = new ArrayCollection();
        $this->noTargetEmbedMany = new ArrayCollection();
        $this->noTargetRefMany   = new ArrayCollection();
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("type")
 * @ODM\DiscriminatorMap({"a"=GH788DocumentListed::class})
 */
class GH788DocumentListed extends GH788Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\Document */
class GH788DocumentUnlisted extends GH788DocumentListed
{
}

/** @ODM\EmbeddedDocument */
class GH788InlineEmbedListed
{
    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\EmbeddedDocument */
class GH788InlineEmbedUnlisted extends GH788InlineEmbedListed
{
}

/** @ODM\Document */
class GH788InlineRefListed
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\Document */
class GH788InlineRefUnlisted extends GH788InlineRefListed
{
}

/**
 * @ODM\EmbeddedDocument
 * @ODM\DiscriminatorField("type")
 * @ODM\DiscriminatorMap({"d"=GH788ExternEmbedListed::class})
 */
class GH788ExternEmbedListed
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\Document */
class GH788ExternEmbedUnlisted extends GH788ExternEmbedListed
{
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("type")
 * @ODM\DiscriminatorMap({"e"=GH788ExternRefListed::class})
 */
class GH788ExternRefListed
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $name;
}

/** @ODM\Document */
class GH788ExternRefUnlisted extends GH788ExternRefListed
{
}
