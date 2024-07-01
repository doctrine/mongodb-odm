Mapping Classes to the ORM and ODM
==================================

Because of the non-intrusive design of Doctrine, it is possible to map PHP
classes to both a relational database (with the Doctrine ORM) and
MongoDB (with the Doctrine MongoDB ODM), or any other persistence layer that
implements the Doctrine Persistence `persistence`_ interfaces.

Test Subject
------------

For this cookbook entry, we need to define a class that can be persisted to both MySQL and MongoDB.
We'll use a ``BlogPost`` as you may want to write some generic blogging functionality that has support
for multiple Doctrine persistence layers:

.. code-block:: php

    <?php

    namespace Documents\Blog;

    class BlogPost
    {
        public int $id;
        public string $title;
        public string $body;
    }

Mapping Information
-------------------

Now we just need to provide the mapping information for the Doctrine persistence
layers so they know how to consume the objects and persist them to the database.

ORM
~~~

First define the mapping for the ORM:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents\Blog;

        use Documents\Blog\Repository\ORM\BlogPostRepository;
        use Doctrine\ORM\Mapping as ORM;

        #[ORM\Entity(repositoryClass: BlogPostRepository::class)]
        class BlogPost
        {
            #[ORM\Id]
            #[ORM\Column(type: 'integer')]
            #[ORM\GeneratedValue(strategy: 'AUTO')]
            public int $id;

            #[ORM\Column(type: 'string')]
            public string $title;

            #[ORM\Column(type: 'text')]
            public string $body;
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                  http://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

            <entity name="Documents\Blog\BlogPost" repository-class="Documents\Blog\Repository\ORM\BlogPostRepository">
                <id name="id" type="int" />
                <field name="name" type="string" />
                <field name="email" type="text" />
            </entity>
        </doctrine-mapping>

Now you are able to persist the ``Documents\Blog\BlogPost`` with an instance of
``EntityManager``:

.. code-block:: php

    <?php

    $blogPost = new BlogPost();
    $blogPost->title = 'Hello World!';

    $em->persist($blogPost);
    $em->flush();

You can find the blog post:

.. code-block:: php

    <?php

    $blogPost = $em->getRepository(BlogPost::class)->findOneBy(['title' => 'Hello World!']);

MongoDB ODM
~~~~~~~~~~~

Now map the same class to the Doctrine MongoDB ODM:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents\Blog;

        use Documents\Blog\Repository\ODM\BlogPostRepository;
        use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

        #[ODM\Document(repositoryClass: BlogPostRepository::class)]
        class BlogPost
        {
            #[ODM\Id(type: 'int', strategy: 'INCREMENT')]
            public int $id;

            #[ODM\Field]
            public string $title;

            #[ODM\Field]
            public string $body;
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                  http://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

            <document name="Documents\Blog\BlogPost" repository-class="Documents\Blog\Repository\ODM\BlogPostRepository">
                <id strategy="INCREMENT" type="int" />
                <field field-name="name" type="string" />
                <field field-name="email" type="text" />
            </document>
        </doctrine-mongo-mapping>

.. note::

    We use the ``INCREMENT`` strategy for the MongoDB ODM for compatibility with
    the ORM mapping. But you can also use the default ``AUTO`` strategy
    and store a generated MongoDB ObjectId as a string in the SQL database.

Now the same class is able to be persisted in the same way using an instance of
``DocumentManager``:

.. code-block:: php

    <?php

    $blogPost = new BlogPost();
    $blogPost->title = 'Hello World!';

    $dm->persist($blogPost);
    $dm->flush();

You can find the blog post:

.. code-block:: php

    <?php

    $blogPost = $dm->getRepository(BlogPost::class)->findOneBy(['title' => 'Hello World!']);

Repository Classes
------------------

You can implement the same repository interface for the ORM and MongoDB ODM
easily, e.g. by creating ``BlogPostRepositoryInterface``:

.. code-block:: php

    <?php
    // An Interface to ensure ORM and ODM Repository classes have the same methods implemented

    namespace Documents\Blog\Repository;

    use Documents\Blog\BlogPost;

    interface BlogPostRepositoryInterface
    {
        public function findPostById(int $id): ?BlogPost;
    }

Define repository methods required by the interface for the ORM:

.. code-block:: php

    <?php

    namespace Documents\Blog\Repository\ORM;

    use Documents\Blog\Repository\BlogPostRepositoryInterface;
    use Doctrine\ORM\EntityRepository;

    class BlogPostRepository extends EntityRepository implements BlogPostRepositoryInterface
    {
        public function findPostById(int $id): ?BlogPost
        {
            return $this->findOneBy(['id' => $id]);
        }
    }

Now define the same repository methods for the MongoDB ODM:

.. code-block:: php

    <?php

    namespace Documents\Blog\Repository\ODM;

    use Documents\Blog\Repository\BlogPostRepositoryInterface;
    use Doctrine\ODM\MongoDB\Repository\DocumentRepository;

    class BlogPostRepository extends DocumentRepository implements BlogPostRepositoryInterface
    {
        public function findPostById(int $id): ?BlogPost
        {
            return $this->findOneBy(['id' => $id]);
        }
    }

As you can see the repositories are the same and the final returned data is the same vanilla
PHP objects. The data is transparently injected to the objects for you automatically so you
are not forced to extend some base class or shape your domain in any certain way for it to work
with the Doctrine persistence layers.

.. note::

    If the same class is mapped to both the ORM and ODM, and you persist the
    instance in both, you will have two separate instances in memory. This is
    because the ORM and ODM are separate libraries and do not share the same
    object manager.

.. _persistence: https://github.com/doctrine/persistence
