<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

class EmbedPersistedDocumentTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSimple()
    {
        $product = new Product();
        $product->title = 'Product';

        $order = new Order();
        $order->title = 'Order';
        $order->product = $product;

        $this->dm->persist($order);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getDocumentCollection(__NAMESPACE__.'\Order')->findOne();

        $this->assertInstanceOf('\MongoId', $test['product']['id']);
        $this->assertEquals('Order', $test['title']);
        $this->assertEquals('Product', $test['product']['title']);

        $doc = $this->dm->findOne(__NAMESPACE__.'\Order');
        $this->assertInstanceOf(__NAMESPACE__.'\Order', $order);
        $this->assertTrue(is_string($doc->product->id));
        $this->assertEquals((string) $test['product']['id'], $doc->product->id);
        $this->assertEquals('Order', $doc->title);
        $this->assertEquals('Product', $doc->product->title);

        $this->dm->clear();

        $order = $this->dm->findOne(__NAMESPACE__.'\Order');
        $this->assertInstanceOf(__NAMESPACE__.'\Order', $order);

        $product = $this->dm->findOne(__NAMESPACE__.'\Product');
        $this->assertInstanceOf(__NAMESPACE__.'\Product', $product);

        $order->product->title = 'tesgttttt';
        $this->dm->flush();
        $this->dm->clear();

        $test1 = $this->dm->getDocumentCollection(__NAMESPACE__.'\Product')->findOne();
        $test2 = $this->dm->getDocumentCollection(__NAMESPACE__.'\Order')->findOne();
        $this->assertNotEquals($test1['title'], $test2['product']['title']);

        $order = $this->dm->findOne(__NAMESPACE__.'\Order');
        $product = $this->dm->findOne(__NAMESPACE__.'\Product');
        $this->assertNotEquals($product->title, $order->product->title);
    }
}

/** @Document */
class Order
{
    /** @Id */
    public $id;

    /** @String */
    public $title;

    /** @EmbedOne(targetDocument="Product", cascade={"persist"}) */
    public $product;
}

/** @Document */
class Product
{
    /** @Id */
    public $id;

    /** @String */
    public $title;
}