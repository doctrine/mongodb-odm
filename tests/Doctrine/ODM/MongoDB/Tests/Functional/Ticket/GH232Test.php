<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class GH232Test extends BaseTestCase
{
    public function testReferencedDocumentInsideEmbeddedDocument(): void
    {
        /* PARENT DOCUMENT */
        $product = new Product('Product');
        /* END PARENT DOCUMENT */

        /* ADD EMBEDDED DOCUMENT */
        $subProduct = new SubProduct();
        $product->subproducts->add($subProduct);

        $price = new Price();
        $subProduct->prices->add($price);
        /* END ADD EMBEDDED DOCUMENT */

        // persist & double flush
        $this->dm->persist($product);
        $this->dm->flush();
        $this->dm->flush();
        $this->dm->clear();

        $product = $this->dm->getRepository(Product::class)->findOneBy(['name' => 'Product']);

        self::assertEquals(1, $product->subproducts->count());
        self::assertEquals(1, $product->subproducts[0]->prices->count());
    }
}

#[ODM\Document]
class Product
{
    /** @var string|null */
    #[ODM\Id]
    protected $id;

    /** @var string */
    #[ODM\Field(type: 'string')]
    public $name;

    /** @var Collection<int, Price>|array<Price> */
    #[ODM\EmbedMany(targetDocument: Price::class)]
    public $prices = [];

    /** @var Collection<int, SubProduct>|array<SubProduct> */
    #[ODM\EmbedMany(targetDocument: SubProduct::class)]
    public $subproducts = [];

    public function __construct(string $name)
    {
        $this->name        = $name;
        $this->subproducts = new ArrayCollection();
    }
}

#[ODM\EmbeddedDocument]
class SubProduct
{
    /** @var Collection<int, Price>|array<Price> */
    #[ODM\EmbedMany(targetDocument: Price::class)]
    public $prices = [];

    public function __construct()
    {
        $this->prices = new ArrayCollection();
    }
}

#[ODM\EmbeddedDocument]
class Price
{
    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $price;
}
