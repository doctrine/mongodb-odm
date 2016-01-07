<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

use Doctrine\Common\Collections\ArrayCollection;

class EmbeddedReferenceTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testReferencedDocumentInsideEmbeddedDocument()
    {
        /* PARENT DOCUMENT */
        $offer = new Offer('My Offer');
        /* END PARENT DOCUMENT */

        /* ADD EMBEDDED DOCUMENT */
        $link1 = new Link('http://link1.com');
        $offer->links->add($link1);
        /* END ADD EMBEDDED DOCUMENT

        /* ADD REFERENCED DOCUMENTS TO EMBEDDED DOCUMENT */
        $referencedDocument1 = new ReferencedDocument('Referenced Document 1');
        $this->dm->persist($referencedDocument1);
        $link1->referencedDocuments->add($referencedDocument1);

        $referencedDocument2 = new ReferencedDocument('Referenced Document 2');
        $this->dm->persist($referencedDocument2);
        $link1->referencedDocuments->add($referencedDocument2);

        $referencedDocument3 = new ReferencedDocument('Referenced Document 3');
        $this->dm->persist($referencedDocument3);
        $link1->referencedDocuments->add($referencedDocument3);

        $referencedDocument4 = new ReferencedDocument('Referenced Document 4');
        $this->dm->persist($referencedDocument4);
        $link1->referencedDocuments->add($referencedDocument4);

        $referencedDocument5 = new ReferencedDocument('Referenced Document 5');
        $this->dm->persist($referencedDocument5);
        $link1->referencedDocuments->add($referencedDocument5);
        /* END ADD REFERENCED DOCUMENTS TO EMBEDDED DOCUMENT */

        // persist & flush
        $this->dm->persist($offer);
        $this->dm->flush();
        $this->dm->clear();

        $offer = $this->dm->getRepository(__NAMESPACE__ . '\Offer')->findOneByName('My Offer');

        // Should be: 1 Link, 5 referenced documents
        // Actual Result: 1 link, 10 referenced documents
        $this->assertEquals(1, $offer->links->count());
        $this->assertEquals(5, $offer->links[0]->referencedDocuments->count());
    }
}

/** @ODM\Document */
class Offer
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedMany(targetDocument="Link") */
    public $links;

    public function __construct($name)
    {
        $this->name = $name;
        $this->links = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class Link
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    public $url;

    /** @ODM\ReferenceMany(targetDocument="ReferencedDocument") */
    public $referencedDocuments;

    public function __construct($url)
    {
        $this->url = $url;
        $this->referencedDocuments = new ArrayCollection();
    }
}

/** @ODM\Document */
class ReferencedDocument
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }
}
