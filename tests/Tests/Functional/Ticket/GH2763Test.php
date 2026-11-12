<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\ObjectId;

class GH2763Test extends BaseTestCase
{
    public function testDefaultValueIsKeptForDirectlyLoadedDocument(): void
    {
        $product = new GH2763Product();
        $this->dm->persist($product);
        $this->dm->flush();

        $this->unsetProductName($product);
        $this->dm->clear();

        $product = $this->dm->find(GH2763Product::class, $product->id);

        self::assertSame('default value', $product->name);
    }

    public function testDefaultValueIsKeptForReferenceManyElement(): void
    {
        $order           = new GH2763Order();
        $order->products = new ArrayCollection([$product = new GH2763Product()]);
        $this->dm->persist($order);
        $this->dm->flush();

        $this->unsetProductName($product);
        $this->dm->clear();

        $order     = $this->dm->find(GH2763Order::class, $order->id);
        $reference = $order->products->first();

        self::assertInstanceOf(GH2763Product::class, $reference);
        self::assertSame('default value', $reference->name);
    }

    public function testDefaultValueIsKeptWhenQueryingDocumentAfterReferenceManyWasLoaded(): void
    {
        $order           = new GH2763Order();
        $order->products = new ArrayCollection([$product = new GH2763Product()]);
        $this->dm->persist($order);
        $this->dm->flush();

        $this->unsetProductName($product);
        $this->dm->clear();

        $order = $this->dm->find(GH2763Order::class, $order->id);
        $order->products->first();

        $product = $this->dm->createQueryBuilder(GH2763Product::class)
            ->field('id')->equals($product->id)
            ->getQuery()
            ->getSingleResult();

        self::assertInstanceOf(GH2763Product::class, $product);
        self::assertSame('default value', $product->name);
    }

    public function testPartialRefreshDoesNotResetInitializedProperty(): void
    {
        $order           = new GH2763Order();
        $order->products = new ArrayCollection([$product = new GH2763Product()]);
        $product->name   = 'custom value';
        $this->dm->persist($order);
        $this->dm->flush();

        $this->dm->clear();

        $order   = $this->dm->find(GH2763Order::class, $order->id);
        $product = $order->products->first();

        self::assertInstanceOf(GH2763Product::class, $product);
        self::assertSame('custom value', $product->name);

        $this->dm->createQueryBuilder(GH2763Product::class)
            ->select('_id')
            ->refresh()
            ->getQuery()
            ->getSingleResult();

        self::assertSame('custom value', $product->name, 'A partial refresh must not reset a property that was previously loaded.');

        $this->dm->flush();

        $document = $this->dm->getDocumentCollection(GH2763Product::class)
            ->findOne(['_id' => new ObjectId($product->id)]);

        self::assertSame('custom value', $document['name'] ?? null);
    }

    private function unsetProductName(GH2763Product $product): void
    {
        $this->dm->getDocumentCollection(GH2763Product::class)
            ->updateOne(['_id' => new ObjectId($product->id)], ['$unset' => ['name' => '']]);
    }
}

#[ODM\Document]
class GH2763Order
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var Collection<int, GH2763Product> */
    #[ODM\ReferenceMany(targetDocument: GH2763Product::class, cascade: ['persist'])]
    public Collection $products;
}

#[ODM\Document]
class GH2763Product
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    #[ODM\Field]
    public string $name = 'default value';
}
