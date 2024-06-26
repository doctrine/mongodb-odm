<?php

declare(strict_types=1);

namespace Documentation\BlendingOrm;

use Doctrine\DBAL\DriverManager;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMSetup;

class BlendingOrmTest extends BaseTestCase
{
    public function testTest(): void
    {
        $dm = $this->dm;

        // Init ORM
        $config     = ORMSetup::createAttributeMetadataConfiguration(
            paths: [__DIR__],
            isDevMode: true,
        );
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'path' => ':memory:',
        ], $config);
        $connection->executeQuery('CREATE TABLE orders (id INTEGER PRIMARY KEY, productId TEXT)');
        $em = new EntityManager($connection, $config);
        $em->getEventManager()
            ->addEventListener(
                [Events::postLoad],
                new BlendingOrmEventSubscriber($dm),
            );

        // Init Product document and Order entity
        $product        = new Product();
        $product->title = 'Test Product';
        $dm->persist($product);
        $dm->flush();

        $order = new Order();
        $order->setProduct($product);
        $em->persist($order);
        $em->flush();
        $em->clear();

        $order = $em->find(Order::class, $order->id);
        // The Product document is loaded from the DocumentManager
        $this->assertSame($product, $order->getProduct());

        $em->clear();
        $dm->clear();

        $order = $em->find(Order::class, $order->id);
        // New Product instance, not the same as the one in the DocumentManager
        $this->assertNotSame($product, $order->getProduct());
        $this->assertSame($product->id, $order->getProduct()->id);
        $this->assertSame($product->title, $order->getProduct()->title);
    }
}
