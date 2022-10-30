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

        self::assertInstanceOf(ObjectId::class, $test['product']['_id']);
        self::assertEquals('Order', $test['title']);
        self::assertEquals('Product', $test['product']['title']);

        $doc = $this->dm->find(Order::class, $order->id);
        self::assertInstanceOf(Order::class, $order);
        self::assertIsString($doc->product->id);
        self::assertEquals((string) $test['product']['_id'], $doc->product->id);
        self::assertEquals('Order', $doc->title);
        self::assertEquals('Product', $doc->product->title);

        $this->dm->clear();

        $order = $this->dm->find(Order::class, $order->id);
        self::assertInstanceOf(Order::class, $order);

        $product = $this->dm->find(Product::class, $product->id);
        self::assertInstanceOf(Product::class, $product);

        $order->product->title = 'tesgttttt';
        $this->dm->flush();
        $this->dm->clear();

        $test1 = $this->dm->getDocumentCollection(Product::class)->findOne();
        $test2 = $this->dm->getDocumentCollection(Order::class)->findOne();
        self::assertNotEquals($test1['title'], $test2['product']['title']);

        $order   = $this->dm->find(Order::class, $order->id);
        $product = $this->dm->find(Product::class, $product->id);
        self::assertNotEquals($product->title, $order->product->title);
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
        self::assertNotNull($category);
        $category->setName('Root Changed');
        $children = $category->getChildren();

        $children[0]->setName('Child 1 Changed');
        $children[0]->getChild('Child 2')->setName('Child 2 Changed');
        $category->addChild('Child 2');
        $this->dm->flush();
        $this->dm->clear();

        $category = $this->dm->find(Category::class, $category->getId());

        $children = $category->getChildren();
        self::assertEquals('Child 1 Changed', $children[0]->getName());
        self::assertEquals('Child 2 Changed', $children[0]->getChild(0)->getName());
        self::assertEquals('Root Changed', $category->getName());
        self::assertCount(2, $category->getChildren());
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

        self::assertNotNull($test);
        $test->getChild('Child 1')->setName('Child 1 Changed');
        $test->getChild('Child 2')->setName('Child 2 Changed');
        $test->setName('Root Changed');
        $child3 = $test->addChild('Child 3');
        $this->dm->persist($child3);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(Hierarchy::class, $test->getId());
        self::assertNotNull($test);
        self::assertEquals('Root Changed', $test->getName());
        self::assertEquals('Child 1 Changed', $test->getChild(0)->getName());
        self::assertEquals('Child 2 Changed', $test->getChild(1)->getName());

        $child3 = $this->dm->getRepository(Hierarchy::class)->findOneBy(['name' => 'Child 3']);
        self::assertNotNull($child3);
        $child3->setName('Child 3 Changed');
        $this->dm->flush();

        $child3 = $this->dm->getRepository(Hierarchy::class)->findOneBy(['name' => 'Child 3 Changed']);
        self::assertNotNull($child3);
        self::assertEquals('Child 3 Changed', $child3->getName());

        $test = $this->dm->getDocumentCollection(Hierarchy::class)->findOne(['name' => 'Child 1 Changed']);
        self::assertArrayNotHasKey('children', $test, 'Test empty array is not stored');
    }
}

/** @ODM\Document */
class Hierarchy
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    private $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    private $name;

    /**
     * @ODM\ReferenceMany(targetDocument=Hierarchy::class)
     *
     * @var Collection<int, Hierarchy>|array<Hierarchy>
     */
    private $children = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param int|string $name
     *
     * @return Hierarchy|null
     */
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

    /**
     * @param string|Hierarchy $child
     *
     * @return Hierarchy
     */
    public function addChild($child)
    {
        if (is_string($child)) {
            $child = new Hierarchy($child);
        }

        $this->children[] = $child;

        return $child;
    }

    /** @return Collection<int, Hierarchy>|array<Hierarchy> */
    public function getChildren()
    {
        return $this->children;
    }
}

/** @ODM\MappedSuperclass */
class BaseCategory
{
    /**
     * @ODM\Field(type="string")
     *
     * @var string
     */
    protected $name;

    /**
     * @ODM\EmbedMany(targetDocument=ChildCategory::class)
     *
     * @var Collection<int, ChildCategory>
     */
    protected $children;

    public function __construct(string $name)
    {
        $this->name     = $name;
        $this->children = new ArrayCollection();
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string|int $name
     *
     * @return ChildCategory|null
     */
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

    /**
     * @param string|ChildCategory $child
     *
     * @return ChildCategory
     */
    public function addChild($child)
    {
        if (is_string($child)) {
            $child = new ChildCategory($child);
        }

        $this->children[] = $child;

        return $child;
    }

    /** @return Collection<int, ChildCategory> */
    public function getChildren(): Collection
    {
        return $this->children;
    }
}

/** @ODM\Document */
class Category extends BaseCategory
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    protected $id;

    public function getId(): ?string
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
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $title;

    /**
     * @ODM\EmbedOne(targetDocument=ProductBackup::class)
     *
     * @var ProductBackup|null
     */
    public $product;
}

/** @ODM\Document */
class Product
{
    /**
     * @ODM\Id
     *
     * @var string|null
     */
    public $id;

    /**
     * @ODM\Field(type="string")
     *
     * @var string|null
     */
    public $title;
}

/** @ODM\EmbeddedDocument */
class ProductBackup extends Product
{
}
