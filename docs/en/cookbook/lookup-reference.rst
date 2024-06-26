Loading references with Lookup
==============================

Doctrine ODM provides a way to load reference documents from other collections
using the ``#[ReferenceOne]`` and ``#[ReferenceMany]`` annotations. This is
perfect to keep independent document updates and avoid data duplication. But
sometimes you need to load the referenced documents with the main document in a
single query. This is where MongoDB's aggregation pipeline and the ``$lookup``
stage come into play.

By default, referenced documents are loaded with a separate query. When you
access them. This is called "lazy loading". However, when you know you will
need the referenced documents, you can use the ``$lookup`` stage in MongoDB's
aggregation pipeline. It's similar to a SQL join, without duplication of data in
the result set when there is many references to load.

Example setup
-------------

For this example, we will use 3 collections:
- ``users``: contains the users who can pass orders
- ``orders`` are affected to a user and contain a list of ``items``
- ``items``: contains the products

The document classes ``User`` and ``Item`` contain only an id and a name.

.. code-block:: php

    <?php

    #[Document]
    class User
    {
        #[Id]
        public string $id;

        public function __construct(
            #[Field(type: 'string')]
            public string $name,
        ) {
        }
    }

    #[Document]
    class Item
    {
        #[Id]
        public string $id;

        public function __construct(
            #[Field(type: 'string')]
            public ?string $name = null,
        ) {
        }
    }

The ``Order`` class has references to one ``User``, a list of ``Item``, an id,
and a date.

.. code-block:: php

    #[Document]
    class Order
    {
        #[Id]
        public string $id;

        #[Field(type: 'date_immutable')]
        public DateTimeImmutable $date;

        /** @var Collection<Item> */
        #[ReferenceMany(
            targetDocument: Item::class,
            cascade: 'all',
            storeAs: 'id',
        )]
        public Collection $items;

        #[ReferenceOne(
            targetDocument: User::class,
            cascade: 'all',
            storeAs: 'id',
        )]
        public User $user;

        public function __construct()
        {
            $this->date  = new DateTimeImmutable();
            $this->items = new ArrayCollection();
        }
    }

In order to make tests, you can import the following documents:

.. code-block:: php

    <?php

    $items = array_map(function ($name) {
        $item = new Item($name);
        $this->dm->persist($item);

        return $item;
    }, ['Wheel', 'Gravel bike', 'Handlebars', 'Sattle', 'Pedals']);

    $user1 = new User('Jacques Anquetil');
    $user2 = new User('Eddy Merckx');
    $dm->persist($user1);
    $dm->persist($user2);

    $order       = new Order();
    $order->date = new DateTimeImmutable('1982-09-01');
    $order->user = $user1;
    $order->items->add($items[0]);
    $order->items->add($items[2]);
    $order->items->add($items[4]);
    $dm->persist($order);

    // Empty order
    $order       = new Order();
    $order->date = new DateTimeImmutable('1974-07-01');
    $order->user = $user1;
    $dm->persist($order);

    $order       = new Order();
    $order->date = new DateTimeImmutable('1965-05-01');
    $order->user = $user2;
    $order->items->add($items[0]);
    $dm->persist($order);

    $dm->flush();
    $dm->clear();

If you run a simple query to get all orders, or an aggregation pipeline
without stage, you will get the following documents with reference ids for
``user`` and ``items``.

.. code-block:: php

    <?php

    [
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061c'),
            'date' => MongoDB\BSON\UTCDateTime('-147398400000'),
            'items' => [
                MongoDB\BSON\ObjectId('667b034c75590cbbe6010613')
            ],
            'user' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010619'),
        ],
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061b'),
            'date' => MongoDB\BSON\UTCDateTime('141868800000'),
            'items' => [],
            'user' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010618'),
        ],
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061a'),
            'date' => MongoDB\BSON\UTCDateTime('399686400000'),
            'items' => [
                MongoDB\BSON\ObjectId('667b034c75590cbbe6010617'),
                MongoDB\BSON\ObjectId('667b034c75590cbbe6010613'),
                MongoDB\BSON\ObjectId('667b034c75590cbbe6010615'),
            ],
            'user' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010618'),
        ]
    ];

Embed a list of referenced documents
------------------------------------

Now, let's see how to load items with each order using an aggregation pipeline.
MongoDB's ``$lookup`` stage requires a local field and a foreign field to match
documents. In our case, these parameters are extracted automatically from the
``#[ReferenceMany]`` mapping. The alias is the name of the field in the
resulting document. In this case, we replace the original list of references
with a list of ``Item`` documents.

.. code-block:: php

    <?php

        $aggregation = $this->dm->createAggregationBuilder(Order::class)
            ->lookup('items')
                ->alias('items');

The result is a list of ``Order`` documents, each one containing a list of ``Item``
documents.

.. code-block:: php

    <?php

    [
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061c'),
            'date' => MongoDB\BSON\UTCDateTime('-147398400000'),
            'items' => [
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010613'),
                    'name' => 'Wheel',
                ]
            ],
            'user' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010619'),
        ],
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061b'),
            'date' => MongoDB\BSON\UTCDateTime('141868800000'),
            'items' => [],
            'user' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010618'),
        ],
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061a'),
            'date' => MongoDB\BSON\UTCDateTime('399686400000'),
            'items' => [
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010617'),
                    'name' => 'Pedals',
                ],
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010613'),
                    'name' => 'Wheel',
                ],
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010615'),
                    'name' => 'Handlebars',
                ]
            ],
            'user' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010618'),
        ]
    ];

Embed a single referenced document
----------------------------------

To get the user, you can also use the ``$lookup`` stage. It will always return a
list of documents. You need to add the ``$unwind`` stage to reduce to a single
document.

.. code-block:: php

    <?php

        $aggregation = $this->dm->createAggregationBuilder(Order::class)
            ->lookup('user')
                ->alias('user')
            ->unwind('$user');

.. code-block:: php

    <?php

    [
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061c'),
            'date' => MongoDB\BSON\UTCDateTime('-147398400000'),
            'items' => [
                MongoDB\BSON\ObjectId('667b034c75590cbbe6010613')
            ],
            'user' => [
                '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010619'),
                'name' => 'Eddy Merckx',
            ],
        ],
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061b'),
            'date' => MongoDB\BSON\UTCDateTime('141868800000'),
            'items' => [],
            'user' => [
                '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010618'),
                'name' => 'Jacques Anquetil',
            ],
        ],
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061a'),
            'date' => MongoDB\BSON\UTCDateTime('399686400000'),
            'items' => [
                MongoDB\BSON\ObjectId('667b034c75590cbbe6010617'),
                MongoDB\BSON\ObjectId('667b034c75590cbbe6010613'),
                MongoDB\BSON\ObjectId('667b034c75590cbbe6010615'),
            ],
            'user' => [
                '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010618'),
                'name' => 'Jacques Anquetil',
            ],
        ]
    ];

Combine multiple lookups
------------------------

Both ``$lookup`` stages can be combined in a single pipeline to get the full
order document, with user and items.

.. code-block:: php

    <?php

        $aggregation = $this->dm->createAggregationBuilder(Order::class)
            ->lookup('items')
                ->alias('items')
            ->lookup('user')
                ->alias('user')
            ->unwind('$user');

.. code-block:: php

    <?php

    [
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061c'),
            'date' => MongoDB\BSON\UTCDateTime('-147398400000'),
            'items' => [
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010613'),
                    'name' => 'Wheel',
                ]
            ],
            'user' => [
                '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010619'),
                'name' => 'Eddy Merckx',
            ],
        ],
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061b'),
            'date' => MongoDB\BSON\UTCDateTime('141868800000'),
            'items' => [],
            'user' => [
                '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010618'),
                'name' => 'Jacques Anquetil',
            ],
        ],
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061a'),
            'date' => MongoDB\BSON\UTCDateTime('399686400000'),
            'items' => [
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010617'),
                    'name' => 'Pedals',
                ],
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010613'),
                    'name' => 'Wheel',
                ],
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010615'),
                    'name' => 'Handlebars',
                ]
            ],
            'user' => [
                '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010618'),
                'name' => 'Jacques Anquetil',
            ],
        ]
    ];

The result is still an array. You may be tempted to hydrate the result into the
``Order`` class, but this will fail because the ``items`` and ``user`` fields
contains embedded documents instead of reference ids as expected by the
``ReferenceMany`` and ``ReferenceOne`` mappings.

Hydrate the result into a custom class
--------------------------------------

You need to create a new class to hold the result of the aggregation.

.. code-block:: php

    <?php

    #[QueryResultDocument]
    class OrderResult
    {
        #[Id]
        public string $id;

        #[Field(type: 'date_immutable')]
        public DateTimeImmutable $date;

        /** @var Collection<Item> */
        #[EmbedMany(targetDocument: Item::class)]
        public Collection $items;

        #[EmbedOne(targetDocument: User::class)]
        public User $user;
    }

.. note::

    You don't need to initialize the collections in the constructor, as the
    ``QueryResultDocument`` is only used to hydrate the results from the
    database and you should never instantiate this class directly.

Now, you can use the ``AggregationBuilder::hydrate()`` method to get the result
as an array of ``OrderResult`` instances.

.. code-block:: php

    <?php

        $aggregation = $this->dm->createAggregationBuilder(Order::class)
            ->hydrate(OrderResult::class)
            ->lookup('items')
                ->alias('items')
            ->lookup('user')
                ->alias('user')
            ->unwind('$user');

.. code-block:: php

    <?php

    [
        new OrderResult(
            id: MongoDB\BSON\ObjectId('667b034c75590cbbe601061c'),
            date: DateTimeImmutable('1965-05-01'),
            items: Doctrine\ODM\MongoDB\PersistentCollection([
                Item(
                    id: '667b034c75590cbbe6010613',
                    name: 'Wheel',
                ),
            ]),
            user: User(
                id: '667b034c75590cbbe6010619',
                name: 'Eddy Merckx',
            ),
        ),
        OrderResult(
            id: MongoDB\BSON\ObjectId('667b034c75590cbbe601061b'),
            date: DateTimeImmutable('1974-07-01'),
            items: Doctrine\ODM\MongoDB\PersistentCollection([]),
            user: User(
                id: '667b034c75590cbbe6010618',
                name: 'Jacques Anquetil',
            ),
        ),
        OrderResult(
            id: MongoDB\BSON\ObjectId('667b034c75590cbbe601061c'),
            date: DateTimeImmutable('1982-09-01'),
            items: Doctrine\ODM\MongoDB\PersistentCollection([
                Item(
                    id: '667b034c75590cbbe6010617',
                    name: 'Pedals',
                ),
                Item(
                    id: '667b034c75590cbbe6010613',
                    name: 'Wheel',
                ),
                Item(
                    id: '667b034c75590cbbe6010615',
                    name: 'Handlebars',
                ),
            ]),
            user: User(
                id: '667b034c75590cbbe6010618',
                name: 'Jacques Anquetil',
            ),
        )
    ];

Perfect, now you know how to load references with the ``$lookup`` and hydrate
the result into a custom class as embedded documents.

Embed relations from another collection
---------------------------------------

Let's see how to embed relations in the inverse way: load users with their
orders. Remember, it's the "order" documents that have a reference to the user.
We now wish to load the users first and use ``$lookup`` to load the list of
orders.

Since the ``User`` class does not have a reference to the ``Order`` class, we
need to specify all the parameters of the ``$lookup`` stage.

.. code-block:: php

    <?php

    $aggregation = $this->dm->createAggregationBuilder(User::class)
        ->sort('name', 'asc')
        ->lookup('Order')
            ->alias('orders')
            ->localField('_id')
            ->foreignField('user');

You get the list of users, with an additional field ``orders`` containing the
list of order documents.

.. code-block:: php

    [
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010619'),
            'name' => 'Eddy Merckx',
            'orders' => [
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061c'),
                    // all other fields
                ]
            ],
        ],
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010618'),
            'name' => 'Jacques Anquetil',
            'orders' => [
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061b'),
                    // all other fields
                ],
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061c'),
                    // all other fields
                ]
            ],
        ]
    ]

Embed two levels of references in a single query
------------------------------------------------

It becomes more complex when you want to load the items of each order. You need
to ``$unwind`` all the orders in separate results, then ``$lookup`` the items
for each order, and finally ``$group`` the orders back to the user.

.. code-block:: php

    <?php

    $aggregation = $this->dm->createAggregationBuilder(User::class)
        // Lookup for the orders of the user
        ->lookup('Order')
            ->alias('orders')
            ->localField('_id')
            ->foreignField('user')

        // Unwind orders so we can use $lookup on the order items
        ->unwind('$orders')
            ->preserveNullAndEmptyArrays(true)

        // Look up the order's items, replacing the references in the order
        ->lookup('Item')
            ->alias('orders.items')
            ->localField('orders.items')
            ->foreignField('_id')

        // Group the orders back by user
        ->group()
            ->field('id')->expression('$_id')
            ->field('root')->first('$$ROOT')
            ->field('orders')->push('$orders')

        // Use $mergeObjects to merge all fields from the document with the
        // order list (with looked up items)
        ->replaceRoot()
            ->mergeObjects([
                '$root',
                ['orders' => '$orders'],
            ]);

The result contains all the users, with the list of orders, and each order
contains the list of items.

.. code-block:: php

    [
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010619'),
            'name' => 'Eddy Merckx',
            'orders' => [
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061c'),
                    'date' => MongoDB\BSON\UTCDateTime('-147398400000'),
                    'items' => [
                        [
                            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010613'),
                            'name' => 'Wheel',
                        ]
                    ],
                ]
            ],
        ],
        [
            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010618'),
            'name' => 'Jacques Anquetil',
            'orders' => [
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061b'),

                    'date' => MongoDB\BSON\UTCDateTime('141868800000'),
                    'items' => [],
                ],
                [
                    '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe601061c'),

                    'date' => MongoDB\BSON\UTCDateTime('399686400000'),
                    'items' => [
                        [
                            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010617'),
                            'name' => 'Pedals',
                        ],
                        [
                            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010613'),
                            'name' => 'Wheel',
                        ],
                        [
                            '_id' => MongoDB\BSON\ObjectId('667b034c75590cbbe6010615'),
                            'name' => 'Handlebars',
                        ]
                    ],
                ]
            ],
        ]
    ]

The last challenge is to hydrate the result into a custom class. You need to
create two classes: one for the user ``UserResult`` that can embed an ``order``
list, and one for the order ``UserOrderResult`` that embeds the items list
but not the user.

.. code-block:: php

    <?php

    #[QueryResultDocument]
    class UserResult
    {
        #[Id]
        public string $id;

        #[Field(type: 'string')]
        public string $name;

        /** @var Collection<UserOrderResult> */
        #[EmbedMany(targetDocument: UserOrderResult::class)]
        public Collection $orders;
    }

    #[QueryResultDocument]
    class UserOrderResult
    {
        #[Id]
        public string $id;

        #[Field(type: 'date_immutable')]
        public DateTimeImmutable $date;

        /** @var Collection<Item> */
        #[EmbedMany(targetDocument: Item::class)]
        public Collection $items;
    }

Adding ``->hydrate(UserResult::class)`` to the previous aggregation builder
will return the result as an array of ``UserResult`` instances.

.. code-block:: php

    [
        UserResult(
            id: '667b034c75590cbbe6010619',
            name: 'Eddy Merckx',
            orders: [
                UserOrderResult(
                    id: '667b034c75590cbbe601061c',
                    date: DateTimeImmutable('1965-05-01'),
                    items: [
                        Item(
                            id: '667b034c75590cbbe6010613',
                            name: 'Wheel',
                        ),
                    ],
                ]
            ],
        ),
        UserResult(
            id: '667b034c75590cbbe6010618',
            name: 'Jacques Anquetil',
            orders: [
                [
                    id: '667b034c75590cbbe601061b',
                    date: DateTimeImmutable('1974-07-01'),
                    items: [],
                ],
                [
                    id: '667b034c75590cbbe601061c',
                    date: DateTimeImmutable('1982-09-01'),
                    items: [
                        Item(
                            id: '667b034c75590cbbe6010617',
                            name: 'Pedals',
                        ),
                        Item(
                            id: '667b034c75590cbbe6010613',
                            name: 'Wheel',
                        ),
                        Item(
                            id: '667b034c75590cbbe6010615',
                            name: 'Handlebars',
                       )]
                    ],
                ]
            ],
        ]
    ]

That's it! You now know how to embed references with the ``$lookup`` stage and
hydrate the result into custom classes.
