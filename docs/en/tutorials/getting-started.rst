Getting Started
===============

Doctrine is a project that aims to handle the persistence of your
domain model in a non-interfering way. Non-relational or no-sql
databases like MongoDB give you flexibility of building data store
around your object model and not vise versa. You can read more on the
initial configuration and setup in :doc:`Introduction to MongoDB Object
Document Mapper <../reference/introduction>`. This section will give you a basic
overview of what could be accomplished using Doctrine MongoDB ODM.

Example Model: Simple Blog
--------------------------

To create the simplest example, let’s assume the following in a simple blog web
application:

-  a Blog has a "user".
-  a Blog "user" can make "blog posts"

A first prototype
-----------------

For the above mentioned example, we start by defining two simple PHP classes:
``User`` and ``BlogPost``. The ``User`` class will have a collection of
``BlogPost`` objects.

.. code-block:: php

    <?php

    namespace Documents;

    class User
    {
        public function __construct(
            public string $name = '',
            public string $email = '',
            public array $posts = [],
        ) {
        }

        // ...
    }

Now define the ``BlogPost`` document that contains the title, body and the date
of creation:

.. code-block:: php

    <?php

    namespace Documents;

    use DateTimeImmutable;

    class BlogPost
    {
        public function __construct(
            public string $title = '',
            public string $body = '',
            public DateTimeImmutable $createdAt = new DateTimeImmutable(),
        ) {
        }

        // ...
    }



Persistent Models
-----------------

To make the above classes persistent, we need to provide Doctrine with some
mapping information so that it knows how to consume the objects and persist
them to the database.

You can provide your mapping information in Annotations or XML:

.. configuration-block::

    .. code-block:: php

        <?php

        use DateTimeImmutable;
        use Doctrine\Common\Collections\ArrayCollection;
        use Doctrine\Common\Collections\Collection;
        use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

        #[ODM\Document]
        class User
        {
            public function __construct(
                #[ODM\Id]
                public ?string $id = null,

                #[ODM\Field]
                public string $name = '',

                #[ODM\Field]
                public string $email = '',

                #[ODM\ReferenceMany(targetDocument: BlogPost::class, cascade: 'all')]
                public Collection $posts = new ArrayCollection(),
            ) {
            }

            // ...
        }

        #[ODM\Document]
        class BlogPost
        {
            public function __construct(
                #[ODM\Id]
                public ?string $id = null,

                #[ODM\Field]
                public string $title = '',

                #[ODM\Field]
                public string $body = '',

                #[ODM\Field]
                public DateTimeImmutable $createdAt = new DateTimeImmutable(),
            ) {
            }

            // ...
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\User">
                <id />
                <field field-name="name" type="string" />
                <field field-name="email" type="string" />
                <reference-many field-name="posts" targetDocument="Documents\BlogPost">
                    <cascade>
                        <all/>
                    </cascade>
                </reference-many>
          </document>
        </doctrine-mongo-mapping>

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\BlogPost">
                <id />
                <field field-name="title" type="string" />
                <field field-name="body" type="string" />
                <field field-name="createdAt" type="date" />
          </document>
        </doctrine-mongo-mapping>

.. note::

   The ``$id`` property above is annotated with the ``#[Id]`` attribute, which
   makes it special. It will be use dby Doctrine to store the unique identifier
   of the document. If you do not provide a value for ``$id``, Doctrine will
   automatically generate an `ObjectId`_ when you persist the document.

That’s it, we have our models, and we can save and retrieve them. Now
all we need to do is to properly instantiate the ``DocumentManager``
instance. Read more about setting up the Doctrine MongoDB ODM in the
:doc:`Introduction to MongoDB Object Document Mapper <../reference/introduction>`:

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Configuration;
    use Doctrine\ODM\MongoDB\DocumentManager;
    use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;

    require_once __DIR__ . '/vendor/autoload.php';

    $config = new Configuration();
    $config->setProxyDir(__DIR__ . '/generated/proxies');
    $config->setProxyNamespace('Proxies');
    $config->setHydratorDir(__DIR__ . '/generated/hydrators');
    $config->setHydratorNamespace('Hydrators');
    $config->setMetadataDriverImpl(AttributeDriver::create(__DIR__ . '/src'));

    $dm = DocumentManager::create(config: $config);

    spl_autoload_register($config->getProxyManagerConfiguration()->getProxyAutoloader());

Usage
-----

Here is how you would use your models now:

.. code-block:: php

    <?php

    // ...

    // create user
    $user = new User(
        name: 'Bulat S.',
        email: 'email@example.com',
    );

    // tell Doctrine to save $user on the next flush()
    $dm->persist($user);

    // create blog post
    $post = new BlogPost(
        title: 'My First Blog Post',
        body: 'MongoDB + Doctrine ODM = awesomeness!',
    );

    // link the blog post to the user
    $user->posts->add($post);

    // store everything to MongoDB
    $dm->flush();

.. note::

    Note that you do not need to explicitly call persist on the ``$post`` because the operation
    will cascade on to the reference automatically.

After running this code, you should have those two objects stored in the
collections "User" and "BlogPost". You can use `MongoDB Compass`_ to inspect
the contents of your database, where you will see this documents:

::

    // BlogPost collection
    {
        _id: ObjectId("4bec5869fdc212081d000000"),
        title: "My First Blog Post",
        body: "MongoDB + Doctrine ODM = awesomeness!",
        createdAt: Date("2010-05-13T18:00:00Z")
    }

    // User collection
    {
        _id: ObjectId("4bec5869fdc212081d010000"),
        name: "Bulat S.",
        email: "email@example.com",
        posts: [
            DBRef("BlogPost", "4bec5869fdc212081d000000")
        ],
    }

You can retrieve the user later by its identifier:

.. code-block:: php

    <?php

    // ...

    $userId = '....';
    $user = $dm->find(User::class, $userId);

Or you can find the user by name even:

.. code-block:: php

    <?php

    $user = $dm->getRepository(User::class)->findOneBy(['name' => 'Bulat S.']);

If you want to iterate over the posts the user references it is as easy as the following:

.. code-block:: php

    <?php

    foreach ($user->posts as $post) {
        echo $post->title;
    }

.. note::

    When retrieved from the database, ``$user->posts`` is an instance of
    ``Doctrine\ODM\MongoDB\PersistentCollection``. This class lazy-loads the
    referenced documents from the database when you access them. It keeps track
    of changes to the collection and will automatically update the database when
    you call ``flush()``.

You will notice that working with objects is nothing magical and you only have
access to the properties and methods that you have defined yourself. You can
continue reading :doc:`Introduction to MongoDB Object Document Mapper <../reference/introduction>`.

.. _MongoDB Compass: https://www.mongodb.com/products/tools/compass
.. _ObjectId: https://www.php.net/class.mongodb-bson-objectid
