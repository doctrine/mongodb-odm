<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class GH232Test extends BaseTest
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

        $product = $this->dm->getRepository(Product::class)->findOneBy(['name' => 'Product']);

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

    /** @ODM\EmbedMany(targetDocument=Price::class) */
    public $prices = [];

    /** @ODM\EmbedMany(targetDocument=SubProduct::class) */
    public $subproducts = [];

    public function __construct($name)
    {
        $this->name = $name;
        $this->subproducts = new ArrayCollection();
    }
}

/** @ODM\EmbeddedDocument */
class SubProduct
{
    /** @ODM\EmbedMany(targetDocument=Price::class) */
    public $prices = [];

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
