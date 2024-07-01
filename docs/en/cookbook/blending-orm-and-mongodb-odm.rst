Blending the ORM and MongoDB ODM
================================

This article demonstrates how you can integrate the `Doctrine MongoDB ODM`_
and the `ORM`_ transparently, maintaining a clean domain model. This is
something that is supported indirectly by the libraries by using the events.

This example will have a ``Product`` that is stored in MongoDB and the ``Order``
stored in a SQL database like MySQL, PostgreSQL or SQLite.

Define Product
--------------

First lets define our ``Product`` document:

.. code-block:: php

    <?php

    namespace Documents;

    #[Document]
    class Product
    {
        #[Id]
        public string $id;

        #[Field(type: 'string')]
        public string $title;
    }

Define Entity
-------------

Next create the ``Order`` entity that has a ``$product`` and ``$productId``
property linking it to the ``Product`` that is stored with MongoDB:

.. code-block:: php

    <?php

    namespace Entities;

    use Documents\Product;

    #[Entity]
    #[Table(name: 'orders')]
    class Order
    {
        #[Id]
        #[Column(type: 'int')]
        #[GeneratedValue(strategy: 'AUTO')]
        public int $id;

        #[Column(type: 'string')]
        private string $productId;

        private Product $product;

        public function getId(): ?int
        {
            return $this->id;
        }

        public function getProductId(): ?string
        {
            return $this->productId;
        }

        public function setProduct(Product $product): void
        {
            $this->productId = $product->getId();
            $this->product = $product;
        }

        public function getProduct(): ?Product
        {
            return $this->product;
        }
    }

Event Subscriber
----------------

Now we need to setup an event subscriber that will set the ``$product`` property
of all ``Order`` instances to a reference to the document product so it can be
lazily loaded when it is accessed the first time. So first register a new event
subscriber:

.. code-block:: php

    <?php

    $eventManager = $em->getEventManager();
    $eventManager->addEventListener(
        [\Doctrine\ORM\Events::postLoad], new MyEventSubscriber($dm)
    );

or in YAML configuration of the Symfony container:

.. code-block:: yaml    
    
    App\Listeners\MyEventSubscriber:
        tags:
            - { name: doctrine.event_listener, connection: default, event: postLoad }

So now we need to define a class named ``MyEventSubscriber`` and pass
``DocumentManager`` as a dependency. It will have a ``postLoad()`` method that
sets the product document reference:

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\DocumentManager;
    use Doctrine\ORM\Event\LifecycleEventArgs;

    class MyEventSubscriber
    {
        public function __construct(
            private readonly DocumentManager $dm,
        ) {}

        public function postLoad(LifecycleEventArgs $eventArgs): void
        {
            $order = $eventArgs->getEntity();

            if (!$order instanceof Order) {
                return;
            }

            $product = $this->dm->getReference(Product::class, $order->getProductId());

            $eventArgs->getObjectManager()
                ->getClassMetadata(Order::class)
                ->reflClass
                ->getProperty('product')
                ->setValue($order, $product);
        }
    }

The ``postLoad`` method will be invoked after an ORM entity is loaded from the database. This allows us 
to use the ``DocumentManager`` to set the ``$product`` property with a reference to the ``Product`` document 
with the product id we previously stored. Please note, that the event subscriber will be called on 
postLoad for all entities that are loaded by doctrine. Thus, it is recommended to check for the current 
entity.  

Working with Products and Orders
--------------------------------

First create a new ``Product``:

.. code-block:: php

    <?php

    $product = new \Documents\Product();
    $product->title = 'Test Product';
    $dm->persist($product);
    $dm->flush();

Now create a new ``Order`` and link it to a ``Product`` in MySQL:

.. code-block:: php

    <?php

    $order = new \Entities\Order();
    $order->setProduct($product);
    $em->persist($order);
    $em->flush();

Later we can retrieve the entity and lazily load the reference to the document
in MongoDB:

.. code-block:: php

    <?php

    $order = $em->find(Order::class, $order->id);

    $product = $order->getProduct();

    echo "Order Title: " . $product->title;

If you were to print the ``$order`` you would see that we got back regular PHP
objects:

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

.. _Doctrine MongoDB ODM: http://www.doctrine-project.org/projects/mongodb_odm
.. _ORM: http://www.doctrine-project.org/projects/orm
