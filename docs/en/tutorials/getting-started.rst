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

To create the simplest example, let’s assume the following in a simple blog web application:

-  Blog has a user.
-  Blog user can make blog posts

A first prototype
-----------------

For the above mentioned example, something as simple as this could be modeled with plain PHP classes.
First define the ``User`` document:

.. code-block:: php

    <?php

    namespace Documents;

    class User
    {
        private $name;
        private $email;
        private $posts = array();

        // ...
    }

Now define the ``BlogPost`` document:

.. code-block:: php

    <?php

    namespace Documents;

    class BlogPost
    {
        private $title;
        private $body;
        private $createdAt;

        // ...
    }

Persistent Models
-----------------

To make the above classes persistent, all we need to do is provide Doctrine with some mapping
information so that it knows how to consume the objects and persist them to the database.

You can provide your mapping information in Annotations, XML, or YAML:

.. configuration-block::

    .. code-block:: php

        <?php
        use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

        /** @ODM\Document */
        class User
        {
            /** @ODM\Id */
            private $id;

            /** @ODM\Field(type="string") */
            private $name;

            /** @ODM\Field(type="string") */
            private $email;

            /** @ODM\ReferenceMany(targetDocument="BlogPost", cascade="all") */
            private $posts = array();

            // ...
        }

        /** @ODM\Document */
        class BlogPost
        {
            /** @ODM\Id */
            private $id;

            /** @ODM\Field(type="string") */
            private $title;

            /** @ODM\Field(type="string") */
            private $body;

            /** @ODM\Field(type="date") */
            private $createdAt;

            // ...
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
                        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
                        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">
          <document name="Documents\User">
                <field fieldName="id" id="true" />
                <field fieldName="name" type="string" />
                <field fieldName="email" type="string" />
                <reference-many fieldName="posts" targetDocument="Documents\BlogPost">
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
                <field fieldName="id" id="true" />
                <field fieldName="title" type="string" />
                <field fieldName="body" type="string" />
                <field fieldName="createdAt" type="date" />
          </document>
        </doctrine-mongo-mapping>

    .. code-block:: yaml

        Documents\User:
          fields:
            id:
              type: id
              id: true
            name:
              type: string
            email:
              type: string
          referenceMany:
            posts:
              targetDocument: Documents\BlogPost
              cascade: all

        Documents\BlogPost:
          fields:
            id:
              type: id
              id: true
            title:
              type: string
            body:
              type: string
            createdAt:
              type: date

That’s it, we have our models, and we can save and retrieve them. Now
all we need to do is to properly instantiate the ``DocumentManager``
instance. Read more about setting up the Doctrine MongoDB ODM in the
:doc:`Introduction to MongoDB Object Document Mapper <../reference/introduction>`:

.. code-block:: php

    <?php

    use Doctrine\MongoDB\Connection;
    use Doctrine\ODM\MongoDB\Configuration;
    use Doctrine\ODM\MongoDB\DocumentManager;
    use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

    AnnotationDriver::registerAnnotationClasses();

    $config = new Configuration();
    $config->setProxyDir('/path/to/generate/proxies');
    $config->setProxyNamespace('Proxies');
    $config->setHydratorDir('/path/to/generate/hydrators');
    $config->setHydratorNamespace('Hydrators');
    $config->setMetadataDriverImpl(AnnotationDriver::create('/path/to/document/classes'));

    $dm = DocumentManager::create(new Connection(), $config);

Usage
-----

Here is how you would use your models now:

.. code-block:: php

    <?php

    // ...

    // create user
    $user = new User();
    $user->setName('Bulat S.');
    $user->setEmail('email@example.com');

    // tell Doctrine 2 to save $user on the next flush()
    $dm->persist($user);

    // create blog post
    $post = new BlogPost();
    $post->setTitle('My First Blog Post');
    $post->setBody('MongoDB + Doctrine 2 ODM = awesomeness!');
    $post->setCreatedAt(new DateTime());

    $user->addPost($post);

    // store everything to MongoDB
    $dm->flush();

.. note::

    Note that you do not need to explicitly call persist on the ``$post`` because the operation
    will cascade on to the reference automatically.

Now if you did everything correctly, you should have those two objects
stored in MongoDB in correct collections and databases. You can use the
`php-mongodb-admin project, hosted on github`_ to look at your
``BlogPost`` collection, where you will see only one document:

::

    Array
    (
        [_id] => 4bec5869fdc212081d000000
        [title] => My First Blog Post
        [body] => MongoDB + Doctrine 2 ODM = awesomeness!
        [createdAt] => MongoDate Object
            (
                [sec] => 1273723200
                [usec] => 0
            )
    )

And the ``User`` collection would consist of the following:

::

    Array
    (
        [_id] => 4bec5869fdc212081d010000
        [name] => Bulat S.
        [email] => email@example.com
        [posts] => Array
            (
                [0] => Array
                    (
                        [$ref] => blog_posts
                        [$id] => 4bec5869fdc212081d000000
                        [$db] => test_database
                    )
            )
    )

You can retrieve the user later by its identifier:

.. code-block:: php

    <?php

    // ...

    $userId = '....';
    $user = $dm->find('User', $userId);

Or you can find the user by name even:

.. code-block:: php

    <?php

    $user = $dm->getRepository('User')->findOneByName('Bulat S.');

If you want to iterate over the posts the user references it is as easy as the following:

.. code-block:: php

    <?php

    $posts = $dm->getPosts();
    foreach ($posts as $post) {
    }

You will notice that working with objects is nothing magical and you only have access to the properties,
getters and setters that you have defined yourself so the semantics are very clear. You can continue
reading about the MongoDB in the :doc:`Introduction to MongoDB Object Document Mapper <../reference/introduction>`.

.. _php-mongodb-admin project, hosted on github: http://github.com/jwage/php-mongodb-admin
