<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class GH566Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFoo()
    {
        $class = __NAMESPACE__ . '\GH566Document';

        $doc1 = new GH566Document();
        $doc2 = new GH566Document();
        $doc3 = new GH566Document();

        $this->dm->persist($doc1);
        $this->dm->persist($doc2);
        $this->dm->persist($doc3);

        $embeddedDoc1 = new GH566EmbeddedDocument();
        $embeddedDoc1->sequence = 1;
        $embeddedDoc1->parent = $doc1;
        $embeddedDoc2 = new GH566EmbeddedDocument();
        $embeddedDoc2->sequence = 2;
        $embeddedDoc2->parent = $doc2;

        $doc3->version = $embeddedDoc2;
        $doc3->versions = new ArrayCollection(array(
            $embeddedDoc1,
            $embeddedDoc2,
        ));

        $this->dm->flush();

        /* The inverse-side $children PersistentCollection on these documents
         * is already initialized by this point, so we need to either clear the
         * DocumentManager or reset the PersistentCollections.
         */
        $doc1->children->setInitialized(false);
        $doc2->children->setInitialized(false);
        $doc3->children->setInitialized(false);

        $doc1 = $this->dm->find($class, $doc1->id);
        $doc1Children = iterator_to_array($doc1->children, false);

        $this->assertCount(0, $doc1Children);

        $doc2 = $this->dm->find($class, $doc2->id);
        $doc2Children = iterator_to_array($doc2->children, false);

        $this->assertCount(1, $doc2Children);
        $this->assertSame($doc3, $doc2Children[0]);

        $doc3 = $this->dm->find($class, $doc3->id);
        $doc3Children = iterator_to_array($doc3->children, false);

        $this->assertCount(0, $doc3Children);
    }
}

/** @ODM\Document */
class GH566Document
{
    /** @ODM\Id */
    public $id;

    /** @ODM\EmbedOne(targetDocument="GH566EmbeddedDocument") */
    public $version;

    /** @ODM\EmbedMany(targetDocument="GH566EmbeddedDocument") */
    public $versions;

    /**
     * @ODM\ReferenceMany(
     *      targetDocument="GH566Document",
     *      cascade={"all"},
     *      mappedBy="version.parent",
     *      sort={"version.sequence"="asc"}
     * )
     */
    public $children;

    public function __construct()
    {
        $this->versions = new ArrayCollection();
        $this->children = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class GH566EmbeddedDocument
{
    /** @ODM\Field(type="int") */
    public $sequence = 0;

    /** @ODM\ReferenceOne(targetDocument="GH566Document", cascade={"all"}, inversedBy="children") */
    public $parent;
}
