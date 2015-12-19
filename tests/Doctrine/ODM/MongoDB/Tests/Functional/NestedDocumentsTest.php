<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class NestedDocumentsTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSimple()
    {
        $product = new Product();
        $product->title = 'Product';

        $order = new Order();
        $order->title = 'Order';

        $this->dm->persist($product);
        $this->dm->persist($order);
        $this->dm->flush();

        $productBackup = new ProductBackup();
        $productBackup->title = $product->title;
        $productBackup->id = $product->id;
        $order->product = $productBackup;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getDocumentCollection(__NAMESPACE__.'\Order')->findOne();

        $this->assertInstanceOf('\MongoId', $test['product']['_id']);
        $this->assertEquals('Order', $test['title']);
        $this->assertEquals('Product', $test['product']['title']);

        $doc = $this->dm->find(__NAMESPACE__.'\Order', $order->id);
        $this->assertInstanceOf(__NAMESPACE__.'\Order', $order);
        $this->assertTrue(is_string($doc->product->id));
        $this->assertEquals((string) $test['product']['_id'], $doc->product->id);
        $this->assertEquals('Order', $doc->title);
        $this->assertEquals('Product', $doc->product->title);

        $this->dm->clear();

        $order = $this->dm->find(__NAMESPACE__.'\Order', $order->id);
        $this->assertInstanceOf(__NAMESPACE__.'\Order', $order);

        $product = $this->dm->find(__NAMESPACE__.'\Product', $product->id);
        $this->assertInstanceOf(__NAMESPACE__.'\Product', $product);

        $order->product->title = 'tesgttttt';
        $this->dm->flush();
        $this->dm->clear();

        $test1 = $this->dm->getDocumentCollection(__NAMESPACE__.'\Product')->findOne();
        $test2 = $this->dm->getDocumentCollection(__NAMESPACE__.'\Order')->findOne();
        $this->assertNotEquals($test1['title'], $test2['product']['title']);

        $order = $this->dm->find(__NAMESPACE__.'\Order', $order->id);
        $product = $this->dm->find(__NAMESPACE__.'\Product', $product->id);
        $this->assertNotEquals($product->title, $order->product->title);
    }

    public function testNestedCategories()
    {
        $category = new Category('Root');
        $child1 = $category->addChild('Child 1');
        $child2 = $child1->addChild('Child 2');
        $this->dm->persist($category);
        $this->dm->flush();
        $this->dm->clear();

        $category = $this->dm->find(__NAMESPACE__.'\Category', $category->getId());
        $this->assertNotNull($category);
        $category->setName('Root Changed');
        $children = $category->getChildren();

        $children[0]->setName('Child 1 Changed');
        $children[0]->getChild('Child 2')->setName('Child 2 Changed');
        $category->addChild('Child 2');
        $this->dm->flush();
        $this->dm->clear();

        $category = $this->dm->find(__NAMESPACE__.'\Category', $category->getId());

        $children = $category->getChildren();
        $this->assertEquals('Child 1 Changed', $children[0]->getName());
        $this->assertEquals('Child 2 Changed', $children[0]->getChild(0)->getName());
        $this->assertEquals('Root Changed', $category->getName());
        $this->assertEquals(2, count($category->getChildren()));
    }

    public function testNestedReference()
    {
        $test = new Hierarchy('Root');
        $child1 = $test->addChild('Child 1');
        $child2 = $test->addChild('Child 2');
        $this->dm->persist($child1);
        $this->dm->persist($child2);
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository(__NAMESPACE__.'\Hierarchy')->findOneBy(array('name' => 'Root'));
 
        $this->assertNotNull($test);
        $child1 = $test->getChild('Child 1')->setName('Child 1 Changed');
        $child2 = $test->getChild('Child 2')->setName('Child 2 Changed');
        $test->setName('Root Changed');
        $child3 = $test->addChild('Child 3');
        $this->dm->persist($child3);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find(__NAMESPACE__.'\Hierarchy', $test->getId());
        $this->assertNotNull($test);
        $this->assertEquals('Root Changed', $test->getName());
        $this->assertEquals('Child 1 Changed', $test->getChild(0)->getName());
        $this->assertEquals('Child 2 Changed', $test->getChild(1)->getName());

        $child3 = $this->dm->getRepository(__NAMESPACE__.'\Hierarchy')->findOneBy(array('name' => 'Child 3'));
        $this->assertNotNull($child3);
        $child3->setName('Child 3 Changed');
        $this->dm->flush();

        $child3 = $this->dm->getRepository(__NAMESPACE__.'\Hierarchy')->findOneBy(array('name' => 'Child 3 Changed'));
        $this->assertNotNull($child3);
        $this->assertEquals('Child 3 Changed', $child3->getName());

        $test = $this->dm->getDocumentCollection(__NAMESPACE__.'\Hierarchy')->findOne(array('name' => 'Child 1 Changed'));
        $this->assertFalse(isset($test['children']), 'Test empty array is not stored');
    }
}

/** @ODM\Document */
class Hierarchy
{
    /** @ODM\Id */
    private $id;

    /** @ODM\Field(type="string") */
    private $name;

    /** @ODM\ReferenceMany(targetDocument="Hierarchy") */
    private $children = array();

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
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

    public function getChildren()
    {
        return $this->children;
    }
}

/** @ODM\MappedSuperclass */
class BaseCategory
{
    /** @ODM\Field(type="string") */
    protected $name;

    /** @ODM\EmbedMany(targetDocument="ChildCategory") */
    protected $children;

    public function __construct($name)
    {
        $this->name = $name;
        $this->children = new ArrayCollection();
    }

    public function setName($name)
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

    public function getChildren()
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

    /** @ODM\EmbedOne(targetDocument="ProductBackup") */
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
