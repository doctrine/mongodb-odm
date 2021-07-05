<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use MongoDB\BSON\ObjectId;

use function is_numeric;
use function is_string;

class NestedDocumentsTest extends BaseTest
{
    public function testSimple(): void
    {
        $product        = new Product();
        $product->title = 'Product';

        $order        = new Order();
        $order->title = 'Order';

        $this->dm->persist($product);
        $this->dm->persist($order);
        $this->dm->flush();

        $productBackup        = new ProductBackup();
        $productBackup->title = $product->title;
        $productBackup->id    = $product->id;
        $order->product       = $productBackup;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getDocumentCollection(Order::class)->findOne();

        $this->assertInstanceOf(ObjectId::class, $test['product']['_id']);
        $this->assertEquals('Order', $test['title']);
        $this->assertEquals('Product', $test['product']['title']);

        $doc = $this->dm->find(Order::class, $order->id);
        $this->assertInstanceOf(Order::class, $order);
        $this->assertIsString($doc->product->id);
        $this->assertEquals((string) $test['product']['_id'], $doc->product->id);
        $this->assertEquals('Order', $doc->title);
        $this->assertEquals('Product', $doc->product->title);

        $this->dm->clear();

        $order = $this->dm->find(Order::class, $order->id);
        $this->assertInstanceOf(Order::class, $order);

        $product = $this->dm->find(Product::class, $product->id);
        $this->assertInstanceOf(Product::class, $product);

        $order->product->title = 'tesgttttt';
        $this->dm->flush();
        $this->dm->clear();

        $test1 = $this->dm->getDocumentCollection(Product::class)->findOne();
        $test2 = $this->dm->getDocumentCollection(Order::class)->findOne();
        $this->assertNotEquals($test1['title'], $test2['product']['title']);

        $order   = $this->dm->find(Order::class, $order->id);
        $product = $this->dm->find(Product::class, $product->id);
        $this->assertNotEquals($product->title, $order->product->title);
    }

    public function testNestedCategories(): void
    {
        $category = new Category('Root');
        $child1   = $category->addChild('Child 1');
        $child2   = $child1->addChild('Child 2');
        $this->dm->persist($category);
        $this->dm->flush();
        $this->dm->clear();

        $category = $this->dm->find(Category::class, $category->getId());
        $this->assertNotNull($category);
        $category->setName('Root Changed');
        $children = $category->getChildren();

        $children[0]->setName('Child 1 Changed');
        $children[0]->getChild('Child 2')->setName('Child 2 Changed');
        $category->addChild('Child 2');
        $this->dm->flush();
        $this->dm->clear();

        $category = $this->dm->find(Category::class, $category->getId());

        $children = $category->getChildren();
        $this->assertEquals('Child 1 Changed', $children[0]->getName());
        $this->assertEquals('Child 2 Changed', $children[0]->getChild(0)->getName());
        $this->assertEquals('Root Changed', $category->getName());
        $this->assertCount(2, $category->getChildren());
    }

    public function testNestedReference(): void
    {
        $test   = new Hierarchy('Root');
        $child1 = $test->addChild('Child 1');
        $child2 = $test->addChild('Child 2');
        $this->dm->persist($child1);
        $this->dm->persist($child2);
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository(Hierarchy::class)->findOneBy(['name' => 'Root']);

        $this->assertNotNull($test);
        $child1 = $test->getChild('Child 1')->setName('Child 1 Changed');
        $child2 = $test->getChild('Child 2')->setName('Child 2 Changed');
        $test->setName('Root Changed');
        $child3 = $test->addChild('Child 3');
        $this->dm->persist($child3);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(Hierarchy::class, $test->getId());
        $this->assertNotNull($test);
        $this->assertEquals('Root Changed', $test->getName());
        $this->assertEquals('Child 1 Changed', $test->getChild(0)->getName());
        $this->assertEquals('Child 2 Changed', $test->getChild(1)->getName());

        $child3 = $this->dm->getRepository(Hierarchy::class)->findOneBy(['name' => 'Child 3']);
        $this->assertNotNull($child3);
        $child3->setName('Child 3 Changed');
        $this->dm->flush();

        $child3 = $this->dm->getRepository(Hierarchy::class)->findOneBy(['name' => 'Child 3 Changed']);
        $this->assertNotNull($child3);
        $this->assertEquals('Child 3 Changed', $child3->getName());

        $test = $this->dm->getDocumentCollection(Hierarchy::class)->findOne(['name' => 'Child 1 Changed']);
        $this->assertArrayNotHasKey('children', $test, 'Test empty array is not stored');
    }
}

/** @ODM\Document */
class Hierarchy
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;

    /** @ODM\ReferenceMany(targetDocument=Hierarchy::class) */
    private $children = [];

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getChild($name)
    {
        if (is_numeric($name)) {
            return $this->children[$name];
        }

        foreach ($this->children as $child) {
            if ($child->name === $name) {
                return $child;
            }
        }

        return null;
    }

    public function addChild($child)
    {
        if (is_string($child)) {
            $child = new Hierarchy($child);
        }

        $this->children[] = $child;

        return $child;
    }

    public function getChildren(): array
    {
        return $this->children;
    }
}

/** @ODM\MappedSuperclass */
class BaseCategory
{
    /** @ODM\Field(type="string") */
    protected $name;

    /** @ODM\EmbedMany(targetDocument=ChildCategory::class) */
    protected $children;

    public function __construct($name)
    {
        $this->name     = $name;
        $this->children = new ArrayCollection();
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getChild($name)
    {
        if (is_numeric($name)) {
            return $this->children[$name];
        }

        foreach ($this->children as $child) {
            if ($child->name === $name) {
                return $child;
            }
        }

        return null;
    }

    public function addChild($child)
    {
        if (is_string($child)) {
            $child = new ChildCategory($child);
        }

        $this->children[] = $child;

        return $child;
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }
}

/** @ODM\Document */
class Category extends BaseCategory
{
    /** @ODM\Id */
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}

/** @ODM\EmbeddedDocument */
class ChildCategory extends BaseCategory
{
}

/** @ODM\Document */
class Order
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $title;

    /** @ODM\EmbedOne(targetDocument=ProductBackup::class) */
    public $product;
}

/** @ODM\Document */
class Product
{
    /** @ODM\Id */
    public $id;

    /** @ODM\Field(type="string") */
    public $title;
}

/** @ODM\EmbeddedDocument */
class ProductBackup extends Product
{
}
