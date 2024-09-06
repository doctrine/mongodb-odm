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

        /** @var Collection<Section> */
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

        /** @var Collection<Section> */
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
