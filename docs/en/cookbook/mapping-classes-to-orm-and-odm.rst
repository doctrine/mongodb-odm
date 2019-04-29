Mapping Classes to the ORM and ODM
==================================

Because of the non-intrusive design of Doctrine, it is possible for you to have plain PHP classes
that are mapped to both a relational database (with the Doctrine2 Object Relational Mapper) and
MongoDB (with the Doctrine MongoDB Object Document Mapper), or any other persistence layer that
implements the Doctrine Common `persistence`_ interfaces.

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
        private $id;
        private $title;
        private $body;

        // ...
    }

Mapping Information
-------------------

Now we just need to provide the mapping information for the Doctrine persistence layers so they know
how to consume the objects and persist them to the database.

ORM
~~~

First define the mapping for the ORM:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents\Blog;

        use Documents\Blog\Repository\ORM\BlogPostRepository;

        /** @Entity(repositoryClass=BlogPostRepository::class) */
        class BlogPost
        {
            /** @Id @Column(type="integer") */
            private $id;

            /** @Column(type="string") */
            private $title;

            /** @Column(type="text") */
            private $body;

            // ...
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                  http://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

            <entity name="Documents\Blog\BlogPost" repository-class="Documents\Blog\Repository\ORM\BlogPostRepository">
                <id name="id" type="integer" />
                <field name="name" type="string" />
                <field name="email" type="text" />
            </entity>
        </doctrine-mapping>

    .. code-block:: yaml

        Documents\Blog\BlogPost:
          repositoryClass: Documents\Blog\ORM\BlogPostRepository
          id:
            id:
              type: integer
          fields:
            title:
              type: string
            body:
              type: text

Now you are able to persist the ``Documents\Blog\BlogPost`` with an instance of ``EntityManager``:

.. code-block:: php

    <?php

    $blogPost = new BlogPost();
    $blogPost->setTitle('test');

    $em->persist($blogPost);
    $em->flush();

You can find the blog post:

.. code-block:: php

    <?php

    $blogPost = $em->getRepository(BlogPost::class)->findOneBy(array('title' => 'test'));

MongoDB ODM
~~~~~~~~~~~

Now map the same class to the Doctrine MongoDB ODM:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents\Blog;

        use Documents\Blog\Repository\ODM\BlogPostRepository;

        /** @Document(repositoryClass=BlogPostRepository::class) */
        class BlogPost
        {
            /** @Id */
            private $id;

            /** @Field(type="string") */
            private $title;

            /** @Field(type="string") */
            private $body;

            // ...
        }

    .. code-block:: xml

        <?xml version="1.0" encoding="UTF-8"?>
        <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping"
              xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
              xsi:schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping
                                  http://www.doctrine-project.org/schemas/orm/doctrine-mapping.xsd">

            <document name="Documents\Blog\BlogPost" repository-class="Documents\Blog\Repository\ODM\BlogPostRepository">
                <field fieldName="id" id="true" strategy="INCREMENT" type="int" />
                <field fieldName="name" type="string" />
                <field fieldName="email" type="text" />
            </document>
        </doctrine-mongo-mapping>

    .. code-block:: yaml

        Documents\BlogPost:
          repositoryClass: Documents\Blog\ODM\MongoDB\BlogPostRepository
          fields:
            id:
              type: id
            title:
              type: string
            body:
              type: text

Now the same class is able to be persisted in the same way using an instance of ``DocumentManager``:

.. code-block:: php

    <?php

    $blogPost = new BlogPost();
    $blogPost->setTitle('test');

    $dm->persist($blogPost);
    $dm->flush();

You can find the blog post:

.. code-block:: php

    <?php

    $blogPost = $dm->getRepository(BlogPost::class)->findOneBy(array('title' => 'test'));

Repository Classes
------------------

You can implement the same repository interface for the ORM and MongoDB ODM easily, e.g. by creating ``BlogPostRepositoryInterface``:

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
        public function findPostById($id)
        {
            return $this->findOneBy(array('id' => $id));
        }
    }

Now define the same repository methods for the MongoDB ODM:

.. code-block:: php

    <?php

    namespace Documents\Blog\Repository\ODM;

    use Documents\Blog\Repository\BlogPostRepositoryInterface;
    use Doctrine\ODM\MongoDB\DocumentRepository;

    class BlogPostRepository extends DocumentRepository implements BlogPostRepositoryInterface
    {
        public function findPostById($id)
        {
            return $this->findOneBy(array('id' => $id));
        }
    }

As you can see the repositories are the same and the final returned data is the same vanilla
PHP objects. The data is transparently injected to the objects for you automatically so you
are not forced to extend some base class or shape your domain in any certain way for it to work
with the Doctrine persistence layers.

.. _persistence: https://github.com/doctrine/common/tree/master/lib/Doctrine/Common/Persistence
