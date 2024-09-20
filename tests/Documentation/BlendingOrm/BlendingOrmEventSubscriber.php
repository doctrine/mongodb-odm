<?php

declare(strict_types=1);

namespace Documentation\BlendingOrm;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\Event\PostLoadEventArgs;

class BlendingOrmEventSubscriber
{
    public function __construct(
        private readonly DocumentManager $dm,
    ) {
    }

    public function postLoad(PostLoadEventArgs $eventArgs): void
    {
        $order = $eventArgs->getObject();

        if (! $order instanceof Order) {
            return;
        }

        // Reference to the Product document, without loading it
        $product = $this->dm->getReference(Product::class, $order->getProductId());

        $eventArgs->getObjectManager()
            ->getClassMetadata(Order::class)
            ->reflClass
            ->getProperty('product')
            ->setValue($order, $product);
    }
}
