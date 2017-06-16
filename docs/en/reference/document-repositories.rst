.. _document_repositories:

Document Repositories
=====================

.. note::

    A repository mediates between the domain and data mapping layers using a
    collection-like interface for accessing domain objects.

In Doctrine, a repository is a class that concentrates code responsible for
querying and filtering your documents. ODM provides you with a default
``DocumentRepository`` for all of your documents:

.. code-block:: php

    <?php

    /* @var $repository \Doctrine\ODM\MongoDB\DocumentRepository */
    $repository = $documentManager->getRepository(User::class);
    $disabledUsers = $repository->findBy(['disabled' => true, 'activated' => true]);

The array passed to ``findBy`` specifies the criteria for which documents are matched.
ODM will assist with converting PHP values to equivalent BSON types whenever possible:

.. code-block:: php

    <?php
    $group = $documentManager->find(Group::class, 123);
    /* @var $repository \Doctrine\ODM\MongoDB\DocumentRepository */
    $repository = $documentManager->getRepository(User::class);
    $usersInGroup = $repository->findBy(['group' => $group]);

The default repository implementation provides the following methods:

- ``find()`` - finds one document by its identifier. This may skip a database query
if the document is already managed by ODM.
- ``findAll()`` - finds all documents in the collection.
- ``findBy()`` - finds all documents matching the given criteria. Additional query
options may be specified (e.g. sort, limit, skip).
- ``findOneBy()`` - finds one document matching the given criteria.
- ``matching()`` - Finds all documents matching the given criteria, as expressed
with Doctrine's Criteria API.

.. note::

    All above methods will include additional criteria specified by :ref:`Filters <filters>`.

.. note::

    Magic ``findBy`` and ``findOneBy`` calls described below are deprecated in 1.2 and
    will be removed in 2.0.

Additional methods that are not defined explicitly in the repository class may also be
used if they follow a specific naming convention:

.. code-block:: php

    <?php

    $group = $documentManager->find(Group::class, 123);
    /* @var $repository \Doctrine\ODM\MongoDB\DocumentRepository */
    $repository = $documentManager->getRepository(User::class);
    $usersInGroup = $repository->findByGroup($group);
    $randomUser = $repository->findOneByStatus('active');

In the above example, ``findByGroup()`` and ``findOneByStatus()`` will be handled by
the ``__call`` method, which intercepts calls to undefined methods. If the invoked
method's name starts with "findBy" or "findOneBy", ODM will attempt to infer mapped
properties from the remainder of the method name ("Group" or "Status" as per example).
The above calls are equivalent to:

.. code-block:: php

    <?php

    $group = $documentManager->find(Group::class, 123);
    /* @var $repository \Doctrine\ODM\MongoDB\DocumentRepository */
    $repository = $documentManager->getRepository(User::class);
    $usersInGroup = $repository->findBy(['group' => $group]);
    $randomUser = $repository->findOneBy(['status' => 'active']);

Custom Repositories
-------------------

A custom repository allows filtering logic to be consolidated into a single class instead
of spreading it throughout a project. A custom repository class may be specified for a
document class like so:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        /** @Document(repositoryClass="Repositories\UserRepository") */
        class User
        {
            /* ... */
        }

    .. code-block:: xml

        <document name="Documents\User" repository-class="Repositories\UserRepository">
            <!-- ... -->
        </document>

    .. code-block:: yaml

        Documents\User:
            repositoryClass: Repositories\\UserRepository
            collection: user
            # ...

The next step is implementing your repository class. In most cases, ODM's default
``DocumentRepository`` class may be extended with additional methods that you need.
More complex cases that require passing additional dependencies to a custom repository
class will be discussed in the next section.

.. code-block:: php

    <?php

    namespace Repositories;

    class UserRepository extends DocumentRepository
    {
        public function findDisabled()
        {
            return $this->findBy(['disabled' => true, 'activated' => true]);
        }
    }

It is also possible to change ODM's default ``DocumentRepository`` to your own
implementation for all documents (unless overridden by the mapping):

.. code-block:: php

    $documentManager->getConfiguration()
        ->setDefaultRepositoryClassName(MyDefaultRepository::class);

Repositories with Additional Dependencies
-----------------------------------------

.. note::

    Implementing your own RepositoryFactory is possible since version 1.0, but the
    ``AbstractRepositoryFactory`` class used in this example is only available since 1.2.

By default, Doctrine assumes that it can instantiate your repositories in same manner
as its default one:

.. code-block:: php

    <?php

    namespace Repositories;

    class UserRepository extends DocumentRepository
    {
        public function __construct(DocumentManager $dm, UnitOfWork $uow, ClassMetadata $classMetadata)
        {
            /* constructor is inherited from DocumentRepository */
            /* ... */
        }
    }

In order to change the way Doctrine instantiates repositories, you will need to implement your own
`RepositoryFactory <https://github.com/doctrine/mongodb-odm/blob/master/lib/Doctrine/ODM/MongoDB/Repository/RepositoryFactory.php>`_

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Repository\AbstractRepositoryFactory;
    use Symfony\Component\EventDispatcher\EventDispatcherInterface;

    final class YourRepositoryFactory extends AbstractRepositoryFactory
    {
        private $eventDispatcher;

        public function __construct(EventDispatcherInterface $eventDispatcher)
        {
            $this->eventDispatcher = $eventDispatcher;
        }

        protected function instantiateRepository($repositoryClassName, DocumentManager $documentManager, ClassMetadata $metadata)
        {
            switch ($repositoryClassName) {
                case UserRepository::class:
                    return new UserRepository($this->eventDispatcher, $documentManager, $metadata);
                default:
                    return new $repositoryClassName($documentManager, $documentManager->getUnitOfWork(), $metadata);
            }
        }
    }

The factory class must then be registered in the ``Configuration``:

.. code-block:: php

    <?php

    $eventDispatcher = $container->get('event_dispatcher');
    $repoFactory = new YourRepositoryFactory($eventDispatcher);
    $configuration = new Configuration();
    // your other config here
    $configuration->setRepositoryFactory($repoFactory);
