<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH788Test extends BaseTest
{
    public function testDocumentWithDiscriminatorMap()
    {
        $listed = new GH788DocumentListed();
        $listed->name = 'listed';

        $unlisted = new GH788DocumentUnlisted();
        $unlisted->name = 'unlisted';

        $this->dm->persist($listed);
        $this->dm->persist($unlisted);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($listed), $listed->id);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788DocumentListed', $doc);
        $this->assertEquals('listed', $doc->name);

        $doc = $this->dm->find(get_class($unlisted), $unlisted->id);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788DocumentUnlisted', $doc);
        $this->assertEquals('unlisted', $doc->name);

        /* Attempting to find the unlisted class by the parent class will not
         * work, as DocumentPersister::addDiscriminatorToPreparedQuery() adds
         * discriminator criteria to the query, which limits the results to
         * known, listed classes.
         */
        $doc = $this->dm->find(get_class($listed), $unlisted->id);
        $this->assertNull($doc);
    }

    public function testEmbedManyWithExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $listed = new GH788ExternEmbedListed();
        $listed->name = 'listed';
        $doc->externEmbedMany[] = $listed;

        $unlisted = new GH788ExternEmbedUnlisted();
        $unlisted->name = 'unlisted';
        $doc->externEmbedMany[] = $unlisted;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);
        $collection = $doc->externEmbedMany;

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788ExternEmbedListed', $collection[0]);
        $this->assertEquals('listed', $collection[0]->name);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788ExternEmbedUnlisted', $collection[1]);
        $this->assertEquals('unlisted', $collection[1]->name);
    }

    public function testEmbedManyWithInlineDiscriminatorMap()
    {
        $doc = new GH788Document();

        $listed = new GH788InlineEmbedListed();
        $listed->name = 'listed';
        $doc->inlineEmbedMany[] = $listed;

        $unlisted = new GH788InlineEmbedUnlisted();
        $unlisted->name = 'unlisted';
        $doc->inlineEmbedMany[] = $unlisted;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);
        $collection = $doc->inlineEmbedMany;

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788InlineEmbedListed', $collection[0]);
        $this->assertEquals('listed', $collection[0]->name);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788InlineEmbedUnlisted', $collection[1]);
        $this->assertEquals('unlisted', $collection[1]->name);
    }

    public function testEmbedManyWithNoTargetAndExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $listed = new GH788ExternEmbedListed();
        $listed->name = 'listed';
        $doc->noTargetEmbedMany[] = $listed;

        $unlisted = new GH788ExternEmbedUnlisted();
        $unlisted->name = 'unlisted';
        $doc->noTargetEmbedMany[] = $unlisted;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);
        $collection = $doc->noTargetEmbedMany;

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788ExternEmbedListed', $collection[0]);
        $this->assertEquals('listed', $collection[0]->name);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788ExternEmbedUnlisted', $collection[1]);
        $this->assertEquals('unlisted', $collection[1]->name);
    }

    public function testEmbedOneWithExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $unlisted = new GH788ExternEmbedUnlisted();
        $unlisted->name = 'unlisted';
        $doc->externEmbedOne = $unlisted;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);

        $this->assertInstanceOf(__NAMESPACE__.'\GH788ExternEmbedUnlisted', $doc->externEmbedOne);
        $this->assertEquals('unlisted', $doc->externEmbedOne->name);
    }

    public function testEmbedOneWithInlineDiscriminatorMap()
    {
        $doc = new GH788Document();

        $unlisted = new GH788InlineEmbedUnlisted();
        $unlisted->name = 'unlisted';
        $doc->inlineEmbedOne = $unlisted;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);

        $this->assertInstanceOf(__NAMESPACE__.'\GH788InlineEmbedUnlisted', $doc->inlineEmbedOne);
        $this->assertEquals('unlisted', $doc->inlineEmbedOne->name);
    }

    public function testEmbedOneWithNoTargetAndExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $unlisted = new GH788ExternEmbedUnlisted();
        $unlisted->name = 'unlisted';
        $doc->noTargetEmbedOne = $unlisted;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);

        $this->assertInstanceOf(__NAMESPACE__.'\GH788ExternEmbedUnlisted', $doc->noTargetEmbedOne);
        $this->assertEquals('unlisted', $doc->noTargetEmbedOne->name);
    }

    public function testRefManyWithExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $listed = new GH788ExternRefListed();
        $listed->name = 'listed';
        $doc->externRefMany[] = $listed;

        $unlisted = new GH788ExternRefUnlisted();
        $unlisted->name = 'unlisted';
        $doc->externRefMany[] = $unlisted;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);
        $collection = $doc->externRefMany;

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788ExternRefListed', $collection[0]);
        $this->assertEquals($listed->id, $collection[0]->id);
        $this->assertEquals('listed', $collection[0]->name);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788ExternRefUnlisted', $collection[1]);
        $this->assertEquals($unlisted->id, $collection[1]->id);
        $this->assertEquals('unlisted', $collection[1]->name);
    }

    public function testRefManyWithInlineDiscriminatorMap()
    {
        $doc = new GH788Document();

        $listed = new GH788InlineRefListed();
        $listed->name = 'listed';
        $doc->inlineRefMany[] = $listed;

        $unlisted = new GH788InlineRefUnlisted();
        $unlisted->name = 'unlisted';
        $doc->inlineRefMany[] = $unlisted;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);
        $collection = $doc->inlineRefMany;

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788InlineRefListed', $collection[0]);
        $this->assertEquals($listed->id, $collection[0]->id);
        $this->assertEquals('listed', $collection[0]->name);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788InlineRefUnlisted', $collection[1]);
        $this->assertEquals($unlisted->id, $collection[1]->id);
        $this->assertEquals('unlisted', $collection[1]->name);
    }

    public function testRefManyWithNoTargetAndExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $listed = new GH788ExternRefListed();
        $listed->name = 'listed';
        $doc->noTargetRefMany[] = $listed;

        $unlisted = new GH788ExternRefUnlisted();
        $unlisted->name = 'unlisted';
        $doc->noTargetRefMany[] = $unlisted;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);
        $collection = $doc->noTargetRefMany;

        $this->assertCount(2, $collection);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788ExternRefListed', $collection[0]);
        $this->assertEquals($listed->id, $collection[0]->id);
        $this->assertEquals('listed', $collection[0]->name);
        $this->assertInstanceOf(__NAMESPACE__.'\GH788ExternRefUnlisted', $collection[1]);
        $this->assertEquals($unlisted->id, $collection[1]->id);
        $this->assertEquals('unlisted', $collection[1]->name);
    }

    public function testRefOneWithExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $unlisted = new GH788ExternRefUnlisted();
        $unlisted->name = 'unlisted';
        $doc->externRefOne = $unlisted;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);

        $this->assertInstanceOf(__NAMESPACE__.'\GH788ExternRefUnlisted', $doc->externRefOne);
        $this->assertEquals($unlisted->id, $doc->externRefOne->id);
        $this->assertEquals('unlisted', $doc->externRefOne->name);
    }

    public function testRefOneWithInlineDiscriminatorMap()
    {
        $doc = new GH788Document();

        $unlisted = new GH788InlineRefUnlisted();
        $unlisted->name = 'unlisted';
        $doc->inlineRefOne = $unlisted;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);

        $this->assertInstanceOf(__NAMESPACE__.'\GH788InlineRefUnlisted', $doc->inlineRefOne);
        $this->assertEquals($unlisted->id, $doc->inlineRefOne->id);
        $this->assertEquals('unlisted', $doc->inlineRefOne->name);
    }

    public function testRefOneWithNoTargetAndExternalDiscriminatorMap()
    {
        $doc = new GH788Document();

        $unlisted = new GH788ExternRefUnlisted();
        $unlisted->name = 'unlisted';
        $doc->noTargetRefOne = $unlisted;

        $this->dm->persist($doc);
        $this->dm->flush();
        $this->dm->clear();

        $doc = $this->dm->find(get_class($doc), $doc->id);

        $this->assertInstanceOf(__NAMESPACE__.'\GH788ExternRefUnlisted', $doc->noTargetRefOne);
        $this->assertEquals($unlisted->id, $doc->noTargetRefOne->id);
        $this->assertEquals('unlisted', $doc->noTargetRefOne->name);
    }
}

/** @ODM\Document */
class GH788Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedMany(targetDocument="GH788ExternEmbedListed") */
    public $externEmbedMany;

    /** @ODM\EmbedOne(targetDocument="GH788ExternEmbedListed") */
    public $externEmbedOne;

    /** @ODM\ReferenceMany(targetDocument="GH788ExternRefListed", cascade="all") */
    public $externRefMany;

    /** @ODM\ReferenceOne(targetDocument="GH788ExternRefListed", cascade="all") */
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
        $this->externEmbedMany = new ArrayCollection();
        $this->externRefMany = new ArrayCollection();
        $this->inlineEmbedMany = new ArrayCollection();
        $this->inlineRefMany = new ArrayCollection();
        $this->noTargetEmbedMany = new ArrayCollection();
        $this->noTargetRefMany = new ArrayCollection();
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"a"="GH788DocumentListed"})
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
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"d"="GH788ExternEmbedListed"})
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
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"e"="GH788ExternRefListed"})
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
