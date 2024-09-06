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

Custom Repositories
-------------------

A custom repository allows filtering logic to be consolidated into a single class instead
of spreading it throughout a project. A custom repository class may be specified for a
document class like so:

.. configuration-block::

    .. code-block:: php

        <?php

        namespace Documents;

        #[Document(repositoryClass: \Repositories\UserRepository::class)]
        class User
        {
            /* ... */
        }

    .. code-block:: xml

        <document name="Documents\User" repository-class="Repositories\UserRepository">
            <!-- ... -->
        </document>

The next step is implementing your repository class. In most cases, ODM's default
``DocumentRepository`` class may be extended with additional methods that you need.
More complex cases that require passing additional dependencies to a custom repository
class will be discussed in the next section.

.. code-block:: php

    <?php

    namespace Repositories;

    class UserRepository extends DocumentRepository
    {
        public function findDisabled(): array
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

By default, Doctrine assumes that it can instantiate your repositories in same manner
as its default one:

.. code-block:: php

    <?php

    namespace Repositories;

    use Doctrine\ODM\MongoDB\DocumentRepository;
    use Doctrine\ODM\MongoDB\DocumentManager;
    use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
    use Doctrine\ODM\MongoDB\UnitOfWork;

    class UserRepository extends DocumentRepository
    {
        public function __construct(DocumentManager $dm, UnitOfWork $uow, ClassMetadata $classMetadata)
        {
            // The constructor arguments are inherited from DocumentRepository
            parent::__construct($dm, $uow, $classMetadata);
        }
    }

In order to change the way Doctrine instantiates repositories, you will need to
implement your own `RepositoryFactory <https://github.com/doctrine/mongodb-odm/blob/2.9.x/lib/Doctrine/ODM/MongoDB/Repository/RepositoryFactory.php>`_

In the following example, we create a custom repository factory to pass Symfony's
event dispatcher to the repository constructor.

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\DocumentRepository;
    use Doctrine\ODM\MongoDB\DocumentManager;
    use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
    use Doctrine\ODM\MongoDB\Repository\AbstractRepositoryFactory;
    use Doctrine\ODM\MongoDB\UnitOfWork;
    use Symfony\Component\EventDispatcher\EventDispatcherInterface;

    final class YourRepositoryFactory extends AbstractRepositoryFactory
    {
        public function __construct(
            private EventDispatcherInterface $eventDispatcher,
        ) {
        }

        protected function instantiateRepository(string $repositoryClassName, DocumentManager $documentManager, ClassMetadata $metadata)
        {
            return match ($repositoryClassName) {
                UserRepository::class => new UserRepository($this->eventDispatcher, $documentManager, $metadata),
                default               => new $repositoryClassName($documentManager, $documentManager->getUnitOfWork(), $metadata),
            };
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
