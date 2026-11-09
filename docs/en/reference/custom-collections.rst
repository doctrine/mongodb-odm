.. _custom_collection:

Custom Collections
==================

By default, Doctrine uses ``ArrayCollection`` implementation of its ``Collection``
interface to hold both embedded and referenced documents. That collection may then
be wrapped by a ``PersistentCollection`` to allow for change tracking and other
persistence-related features.

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;

    #[Document]
    class Application
    {
        // ...

        /** @var Collection<int, Section> */
        #[EmbedMany(targetDocument: Section::class)]
        public Collection $sections;

        public function __construct()
        {
            $this->sections = new ArrayCollection();
        }

        // ...
    }

For most cases this solution is sufficient but more sophisticated domains could use
their own collections (e.g. a collection that ensures its contained objects are sorted)
or to simply add common filtering methods that otherwise would otherwise be added to
owning document's class.

Custom Collection Classes
-------------------------

Using your own ``Collection`` implementation is as simple as specifying the
``collectionClass`` parameter in the ``#[EmbedMany]`` or ``#[ReferenceMany]`` mapping
and ensuring that your custom class is initialized in the owning class' constructor:

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\ArrayCollection;

    #[Document]
    class Application
    {
        // ...

        /** @var Collection<int, Section> */
        #[EmbedMany(
            collectionClass: SectionCollection::class,
            targetDocument: Section::class,
        )]
        private Collection $sections;

        public function __construct()
        {
            $this->sections = new SectionCollection();
        }

        // ...
    }

If you are satisfied with ``ArrayCollection`` and only want
to sprinkle it with some filtering methods, you may just extend it:

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\ArrayCollection;

    class SectionCollection extends ArrayCollection
    {
        public function getEnabled(): SectionCollection
        {
            return $this->filter(function(Section $s) {
                return $s->isEnabled();
            });
        }
    }

Alternatively, you may want to implement the whole class from scratch:

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\Collection;

    class SectionCollection implements Collection
    {
        public function __construct(
            private array $elements = []
        ) {
        }

        // your implementation of all methods interface requires
    }

Delegated Collection Classes
----------------------------

A custom collection can delegate to the managed collection instead of
implementing the ``Collection`` interface. The ODM then uses the
``PersistentCollection`` directly and does not generate a persistent collection
class. This lets the custom class be ``final`` and keeps lazy loading and
change tracking on the managed collection.

The class must implement
``Doctrine\ODM\MongoDB\PersistentCollection\DelegateCollection`` and declare
exactly one non-static, non-readonly property typed as
``Doctrine\Common\Collections\Collection``:

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;
    use Doctrine\ODM\MongoDB\PersistentCollection\DelegateCollection;
    use Traversable;

    final class SectionCollection implements DelegateCollection
    {
        /** @param Collection<int, Section> $collection */
        public function __construct(private Collection $collection = new ArrayCollection())
        {
        }

        public function getIterator(): Traversable
        {
            return $this->collection->getIterator();
        }

        /** @return list<Section> */
        public function getEnabled(): array
        {
            return array_values(array_filter(
                $this->collection->toArray(),
                static fn (Section $section): bool => $section->isEnabled(),
            ));
        }
    }

The ODM reads and writes the managed collection in that property through
reflection. Read it on each call: do not cache it in another property or in a
closure, because the ODM replaces it when the collection is initialized.

.. note::

    Do not implement ``DelegateCollection`` on a class that extends
    ``ArrayCollection``: its elements are stored in a private array, not in a
    ``Collection`` property. Extend ``AbstractDelegateCollection`` instead.

The class is inferred from the property type, whatever the mapping driver.
Declare it with the ``collectionClass`` mapping option when the property has a
broader type, for instance ``Collection`` with a class extending
``AbstractDelegateCollection``:

.. code-block:: php

    <?php

    #[Document]
    class Application
    {
        /** @var Collection<int, Section> */
        #[EmbedMany(
            collectionClass: SectionCollection::class,
            targetDocument: Section::class,
        )]
        private Collection $sections;

        public function __construct()
        {
            $this->sections = new SectionCollection();
        }
    }

If you want the full ``Collection`` API without writing forwarding methods,
extend ``Doctrine\ODM\MongoDB\PersistentCollection\AbstractDelegateCollection``.
Its mutating methods are final and always write to the managed collection. Put
domain rules in your own methods:

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\PersistentCollection\AbstractDelegateCollection;

    /** @template-extends AbstractDelegateCollection<int, Section> */
    final class SectionCollection extends AbstractDelegateCollection
    {
        public function addSection(Section $section): void
        {
            $this->add($section);
        }

        /** @return list<Section> */
        public function getEnabled(): array
        {
            return array_values($this->filter(
                static fn (Section $section): bool => $section->isEnabled(),
            )->toArray());
        }
    }

Inherited methods such as ``filter()``, ``map()`` and ``matching()`` return a
plain collection, never a ``SectionCollection``.

.. note::

    The ODM, the ``UnitOfWork`` and the collection events see the underlying
    ``PersistentCollection``. Read the document property to get the custom
    collection. The deprecated ``ClassMetadata::$reflFields`` API does not
    unwrap the collection.

Delegated Collection's Constructor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The default factory calls the constructor without arguments. When the
constructor requires dependencies, configure a ``DelegateCollectionFactory``:

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\Collection;
    use Doctrine\ODM\MongoDB\PersistentCollection\DelegateCollection;
    use Doctrine\ODM\MongoDB\PersistentCollection\DelegateCollectionFactory;

    final class YourDelegateCollectionFactory implements DelegateCollectionFactory
    {
        public function __construct(private EventDispatcherInterface $eventDispatcher)
        {
        }

        public function create(string $collectionClass): DelegateCollection
        {
            return match ($collectionClass) {
                SectionCollection::class => new SectionCollection($this->eventDispatcher),
                default => new $collectionClass(),
            };
        }
    }

The factory is registered in the ``Configuration``:

.. code-block:: php

    <?php

    $configuration = new Configuration();
    $configuration->setDelegateCollectionFactory(
        new YourDelegateCollectionFactory($container->get('event_dispatcher')),
    );

The factory is called when a document is loaded from the database. On a new
document, the instance created in the document's constructor is kept.

Classes that implement ``Collection`` without ``DelegateCollection`` keep using
the generated persistent collection classes described above.

Taking Control of the Collection's Constructor
----------------------------------------------

By default, Doctrine assumes that it can instantiate your collections in same
manner as an ``ArrayCollection`` (i.e. the only parameter is an optional PHP
array); however, you may want to inject additional dependencies into your
custom collection class(es).

For this example, we assume that you want to pass Symfony's event dispatcher
to your custom collection class. To do this, you need to modify the
constructor to accept this dependency. You also need to override the
``createFrom`` method to pass the dependency to the collection constructor when
methods such as ``map`` or ``filter`` are called:

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\Common\Collections\Collection;

    class SectionCollection extends ArrayCollection
    {
        public function __construct(
            private EventDispatcherInterface $eventDispatcher,
            private array $elements = [],
        ) {
        }

        public function createFrom(array $elements): static
        {
            return new static($this->eventDispatcher, $elements);
        }

        // your custom methods
    }

When you instantiate a new document, it's your responsibility to pass the
dependency to the collection constructor.

.. code-block:: php

    <?php

    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher */
    $eventDispatcher = $container->get('event_dispatcher');
    $sections = new SectionCollection($eventDispatcher);
    $application = new Application($sections);

The ``$sections`` property cannot have a default value in the ``Application``
class::

.. code-block:: php

    <?php

    #[Document]
    class Application
    {
        #[EmbedMany(
            collectionClass: SectionCollection::class,
            targetDocument: Section::class,
        )]
        private Collection $sections;

        public function __construct(
            SectionCollection $sections,
        ) {
            $this->sections = $sections;
        }
    }

In addition, you need to create a class that implement ``PersistentCollectionFactory``,
which Doctrine ODM will then use to construct its persistent collections.
You should extend ``AbstractPersistentCollectionFactory``:

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\Collection;
    use Doctrine\ODM\MongoDB\PersistentCollection\AbstractPersistentCollectionFactory;
    use Symfony\Component\EventDispatcher\EventDispatcherInterface;

    final class YourPersistentCollectionFactory extends AbstractPersistentCollectionFactory
    {
        public function __construct(
            private EventDispatcherInterface $eventDispatcher,
        ) {}

        protected function createCollectionClass(string $collectionClass): Collection
        {
            return match ($collectionClass) {
                SectionCollection::class => new SectionCollection([], $this->eventDispatcher),
                default                  => new $collectionClass(),
            };
        }
    }

The factory class is then registered in the ``Configuration``:

.. code-block:: php

    <?php

    $eventDispatcher = $container->get('event_dispatcher');
    $collFactory = new YourPersistentCollectionFactory($eventDispatcher);
    $configuration = new Configuration();
    // your other config here
    $configuration->setPersistentCollectionFactory($collFactory);
