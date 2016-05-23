Bi-Directional References
=========================

By default when you map a bi-directional reference, the reference is maintained on both sides
of the relationship and there is not a single "owning side". Both sides are considered owning
and changes are tracked and persisted separately. Here is an example:

.. code-block:: php

    <?php

    /** @Document */
    class BlogPost
    {
        // ...

        /** @ReferenceOne(targetDocument="User") */
        private $user;
    }

    /** @Document */
    class User
    {
        // ...

        /** @ReferenceMany(targetDocument="BlogPost") */
        private $posts;
    }

When I persist some instances of the above classes the references would exist on both sides! The
``BlogPost`` collection would have a `DBRef`_ stored on the ``$user`` property and the ``User``
collection would have a `DBRef`_ stored in the ``$posts`` property.

Owning and Inverse Sides
------------------------

A user may have lots of posts and we don't need to store a reference to each post on the user, we
can get the users post by running a query like the following:

.. code-block:: javascript

    db.BlogPost.find({ 'user.$id' : user.id })

In order to map this you can use the ``inversedBy`` and ``mappedBy`` options. Here is the same
example above where we implement this:

One to Many
~~~~~~~~~~~

.. code-block:: php

    <?php

    /** @Document */
    class BlogPost
    {
        // ...

        /** @ReferenceOne(targetDocument="User", inversedBy="posts") */
        private $user;
    }

    /** @Document */
    class User
    {
        // ...

        /** @ReferenceMany(targetDocument="BlogPost", mappedBy="user") */
        private $posts;
    }

So now when we persist a ``User`` and multiple ``BlogPost`` instances for that ``User``:

.. code-block:: php

    <?php

    $user = new User();

    $post1 = new BlogPost();
    $post1->setUser($user);

    $post2 = new BlogPost();
    $post2->setUser($user);

    $post3 = new BlogPost();
    $post3->setUser($user);

    $dm->persist($post1);
    $dm->persist($post2);
    $dm->persist($post3);
    $dm->flush();

And we retrieve the ``User`` later to access the posts for that user:

.. code-block:: php

    <?php

    $user = $dm->find('User', $user->id);

    $posts = $user->getPosts();
    foreach ($posts as $post) {
        // ...
    }

The above will execute a query like the following to lazily load the collection of posts to
iterate over:

.. code-block:: javascript

    db.BlogPost.find( { 'user.$id' : user.id } )

.. note::

    Remember that the inverse side, the side which specified ``mappedBy`` is immutable and
    any changes to the state of the reference will not be persisted.

Other Examples
--------------

Here are several examples which implement the ``inversedBy`` and ``mappedBy`` options:

One to One
~~~~~~~~~~~

Here is an example where we have a one to one relationship between ``Cart`` and ``Customer``:

.. code-block:: php

    <?php

    /** @Document */
    class Cart
    {
        // ...

        /**
         * @ReferenceOne(targetDocument="Customer", inversedBy="cart")
         */
        public $customer;
    }

    /** @Document */
    class Customer
    {
        // ...

        /**
         * @ReferenceOne(targetDocument="Cart", mappedBy="customer")
         */
        public $cart;
    }

The owning side is on ``Cart.customer`` and the ``Customer.cart`` referenced is loaded with a query
like this:

.. code-block:: javascript

    db.Cart.find( { 'customer.$id' : customer.id } )

If you want to nullify the relationship between a ``Cart`` instance and ``Customer`` instance
you must null it out on the ``Cart.customer`` side:

.. code-block:: php

    <?php

    $cart->setCustomer(null);
    $dm->flush();

.. note::

    When specifying inverse one-to-one relationships the referenced document is
    loaded directly when the owning document is hydrated instead of using a
    proxy. In the example above, loading a ``Customer`` object from the database
    would also cause the corresponding ``Cart`` to be loaded. This can cause
    performance issues when loading many ``Customer`` objects at once.

Self-Referencing Many to Many
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    namespace Documents;

    /** @Document */
    class User
    {
        // ...

        /**
         * @ReferenceMany(targetDocument="User", mappedBy="myFriends")
         */
        public $friendsWithMe;

        /**
         * @ReferenceMany(targetDocument="User", inversedBy="friendsWithMe")
         */
        public $myFriends;

        public function __construct($name)
        {
            $this->name = $name;
            $this->friendsWithMe = new \Doctrine\Common\Collections\ArrayCollection();
            $this->myFriends = new \Doctrine\Common\Collections\ArrayCollection();
        }

        public function addFriend(User $user)
        {
            $user->friendsWithMe[] = $this;
            $this->myFriends[] = $user;
        }
    }

.. _DBRef: https://docs.mongodb.com/manual/reference/database-references/#dbrefs
