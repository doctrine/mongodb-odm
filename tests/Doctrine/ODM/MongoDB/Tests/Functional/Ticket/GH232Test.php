<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * @since 6/26/12
 */
class GH232Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testReferencedDocumentInsideEmbeddedDocument()
    {
        /* PARENT DOCUMENT */
        $product = new Product('Product');
        /* END PARENT DOCUMENT */

        /* ADD EMBEDDED DOCUMENT */
        $sub_product = new SubProduct();
        $product->subproducts->add($sub_product);

        $price = new Price();
        $sub_product->prices->add($price);
        /* END ADD EMBEDDED DOCUMENT */

        // persist & double flush
        $this->dm->persist($product);
        $this->dm->flush();
        $this->dm->flush();
        $this->dm->clear();

        $product = $this->dm->getRepository(__NAMESPACE__ . '\Product')->findOneByName('Product');

        $this->assertEquals(1, $product->subproducts->count());
        $this->assertEquals(1, $product->subproducts[0]->prices->count());
    }
}

/** @ODM\Document */
class Product
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    public $name;

    /** @ODM\EmbedMany(targetDocument="Price") */
    public $prices = array();

    /** @ODM\EmbedMany(targetDocument="SubProduct") */
    public $subproducts = array();

    public function __construct($name)
    {
        $this->name = $name;
        $this->subproducts = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class SubProduct
{
    /** @ODM\EmbedMany(targetDocument="Price") */
    public $prices = array();

    public function __construct()
    {
        $this->prices = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class Price
{
    /** @ODM\Field(type="string") */
    public $price;
}
