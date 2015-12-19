Blending the ORM and MongoDB ODM
================================

Since the start of the `Doctrine MongoDB Object Document Mapper`_ project people have asked how it can be integrated with the `ORM`_. This article will demonstrates how you can integrate the two transparently, maintaining a clean domain model.

This example will have a `Product` that is stored in MongoDB and the `Order` stored in a MySQL database.

Define Product
--------------

First lets define our `Product` document:

.. code-block:: php

    <?php

    namespace Documents;

    /** @Document */
    class Product
    {
        /** @Id */
        private $id;

        /** @Field(type="string") */
        private $title;

        public function getId()
        {
            return $this->id;
        }

        public function getTitle()
        {
            return $this->title;
        }

        public function setTitle($title)
        {
            $this->title = $title;
        }
    }

Define Entity
-------------

Next create the `Order` entity that has a `$product` and `$productId` property linking it to the `Product` that is stored with MongoDB:

.. code-block:: php

    <?php

    namespace Entities;

    use Documents\Product;

    /**
     * @Entity
     * @Table(name="orders")
     */
    class Order
    {
        /**
         * @Id @Column(type="integer")
         * @GeneratedValue(strategy="AUTO")
         */
        private $id;

        /**
         * @Column(type="string")
         */
        private $productId;

        /**
         * @var Documents\Product
         */
        private $product;

        public function getId()
        {
            return $this->id;
        }

        public function getProductId()
        {
            return $this->productId;
        }

        public function setProduct(Product $product)
        {
            $this->productId = $product->getId();
            $this->product = $product;
        }

        public function getProduct()
        {
            return $this->product;
        }
    }

Event Subscriber
----------------

Now we need to setup an event subscriber that will set the `$product` property of all `Order` instances to a reference to the document product so it can be lazily loaded when it is accessed the first time. So first register a new event subscriber:

.. code-block:: php

    <?php

    $eventManager = $em->getEventManager();
    $eventManager->addEventListener(
        array(\Doctrine\ORM\Events::postLoad), new MyEventSubscriber($dm)
    );

So now we need to define a class named `MyEventSubscriber` and pass a dependency to the `DocumentManager`. It will have a `postLoad()` method that sets the product document reference:

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\DocumentManager;
    use Doctrine\ORM\Event\LifecycleEventArgs;

    class MyEventSubscriber
    {
        public function __construct(DocumentManager $dm)
        {
            $this->dm = $dm;
        }

        public function postLoad(LifecycleEventArgs $eventArgs)
        {
            $order = $eventArgs->getEntity();
            $em = $eventArgs->getEntityManager();
            $productReflProp = $em->getClassMetadata('Entities\Order')
                ->reflClass->getProperty('product');
            $productReflProp->setAccessible(true);
            $productReflProp->setValue(
                $order, $this->dm->getReference('Documents\Product', $order->getProductId())
            );
        }
    }

The `postLoad` method will be invoked after an ORM entity is loaded from the database. This allows us to use the `DocumentManager` to set the `$product` property with a reference to the `Product` document with the product id we previously stored.

Working with Products and Orders
--------------------------------

First create a new `Product`:

.. code-block:: php

    <?php

    $product = new \Documents\Product();
    $product->setTitle('Test Product');
    $dm->persist($product);
    $dm->flush();

Now create a new `Order` and link it to a `Product` in MySQL:

.. code-block:: php

    <?php

    $order = new \Entities\Order();
    $order->setProduct($product);
    $em->persist($order);
    $em->flush();

Later we can retrieve the entity and lazily load the reference to the document in MongoDB:

.. code-block:: php

    <?php

    $order = $em->find('Order', $order->getId());

    // Instance of an uninitialized product proxy
    $product = $order->getProduct();

    // Initializes proxy and queries the database
    echo "Order Title: " . $product->getTitle();

If you were to print the `$order` you would see that we got back regular PHP objects:

.. code-block:: php

    <?php

    print_r($order);

The above would output the following:

.. code-block:: php

    Order Object
    (
        [id:Entities\Order:private] => 53
        [productId:Entities\Order:private] => 4c74a1868ead0ed7a9000000
        [product:Entities\Order:private] => Proxies\DocumentsProductProxy Object
            (
                [__isInitialized__] => 1
                [id:Documents\Product:private] => 4c74a1868ead0ed7a9000000
                [title:Documents\Product:private] => Test Product
            )
    )

.. _Doctrine MongoDB Object Document Mapper: http://www.doctrine-project.org/projects/mongodb_odm
.. _ORM: http://www.doctrine-project.org/projects/orm
