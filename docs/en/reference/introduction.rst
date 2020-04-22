Introduction
============

Doctrine MongoDB Object Document Mapper is built for PHP 5.3.0+ and
provides transparent persistence for PHP objects to the popular `MongoDB`_ database by `10gen`_.

Features Overview
-----------------

-  Transparent persistence.
-  Map one or many embedded documents.
-  Map one or many referenced documents.
-  Create references between documents in different databases.
-  Map documents with Annotations, XML or plain old PHP code.
-  Documents can be stored in GridFS buckets.
-  Collection per class(concrete) and single collection inheritance supported.
-  Map your Doctrine 2 ORM Entities to the ODM and use mixed data stores.
-  Inserts are performed using `MongoDB\Collection::insertMany() <https://docs.mongodb.com/php-library/current/reference/method/MongoDBCollection-insertMany/>`_
-  Updates are performed using atomic operators.

Here is a quick example of some PHP object documents that demonstrates a few of the features:

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
    use DateTime;

    /** @ODM\MappedSuperclass */
    abstract class BaseEmployee
    {
        /** @ODM\Id */
        private $id;

        /** @ODM\Field(type="int", strategy="increment") */
        private $changes = 0;

        /** @ODM\Field(type="collection") */
        private $notes = [];

        /** @ODM\Field(type="string") */
        private $name;

        /** @ODM\Field(type="int") */
        private $salary;

        /** @ODM\Field(type="date") */
        private $started;

        /** @ODM\Field(type="date") */
        private $left;

        /** @ODM\EmbedOne(targetDocument=Address::class) */
        private $address;

        public function getId(): ?string { return $this->id; }

        public function getChanges(): int { return $this->changes; }
        public function incrementChanges(): void { $this->changes++; }

        public function getNotes(): array { return $this->notes; }
        public function addNote($note) { $this->notes[] = $note; }

        public function getName(): ?string { return $this->name; }
        public function setName(string $name): void { $this->name = $name; }

        public function getSalary(): ?int { return $this->salary; }
        public function setSalary(int $salary): void { $this->salary = (int) $salary; }

        public function getStarted(): ?DateTime { return $this->started; }
        public function setStarted(DateTime $started) { $this->started = $started; }

        public function getLeft(): ?DateTime { return $this->left; }
        public function setLeft(DateTime $left) { $this->left = $left; }

        public function getAddress(): ?Address { return $this->address; }
        public function setAddress(Address $address): void { $this->address = $address; }
    }

    /** @ODM\Document */
    class Employee extends BaseEmployee
    {
        /** @ODM\ReferenceOne(targetDocument=Manager::class) */
        private $manager;

        public function getManager(): ?Manager { return $this->manager; }
        public function setManager(Manager $manager): void { $this->manager = $manager; }
    }

    /** @ODM\Document */
    class Manager extends BaseEmployee
    {
        /** @ODM\ReferenceMany(targetDocument=Project::class) */
        private $projects;

        public function __construct() { $this->projects = new ArrayCollection(); }

        public function getProjects(): Collection { return $this->projects; }
        public function addProject(Project $project): void { $this->projects[] = $project; }
    }

    /** @ODM\EmbeddedDocument */
    class Address
    {
        /** @ODM\Field(type="string") */
        private $address;

        /** @ODM\Field(type="string") */
        private $city;

        /** @ODM\Field(type="string") */
        private $state;

        /** @ODM\Field(type="string") */
        private $zipcode;

        public function getAddress(): ?string { return $this->address; }
        public function setAddress(string $address): void { $this->address = $address; }

        public function getCity(): ?string { return $this->city; }
        public function setCity(string $city): void { $this->city = $city; }

        public function getState(): ?string { return $this->state; }
        public function setState(string $state): void { $this->state = $state; }

        public function getZipcode(): ?string { return $this->zipcode; }
        public function setZipcode(string $zipcode): void { $this->zipcode = $zipcode; }
    }

    /** @ODM\Document */
    class Project
    {
        /** @ODM\Id */
        private $id;

        /** @ODM\Field(type="string") */
        private $name;

        public function __construct($name) { $this->name = $name; }

        public function getId(): ?string { return $this->id; }

        public function getName(): ?string { return $this->name; }
        public function setName(string $name): void { $this->name = $name; }
    }

Now those objects can be used just like you weren't using any
persistence layer at all and can be persisted transparently by
Doctrine:

.. code-block:: php

    <?php

    use Documents\Employee;
    use Documents\Address;
    use Documents\Project;
    use Documents\Manager;
    use DateTime;

    $employee = new Employee();
    $employee->setName('Employee');
    $employee->setSalary(50000);
    $employee->setStarted(new DateTime());

    $address = new Address();
    $address->setAddress('555 Doctrine Rd.');
    $address->setCity('Nashville');
    $address->setState('TN');
    $address->setZipcode('37209');
    $employee->setAddress($address);

    $project = new Project('New Project');
    $manager = new Manager();
    $manager->setName('Manager');
    $manager->setSalary(100000);
    $manager->setStarted(new DateTime());
    $manager->addProject($project);

    $dm->persist($employee);
    $dm->persist($address);
    $dm->persist($project);
    $dm->persist($manager);
    $dm->flush();

The above would insert the following:

::

    Array
    (
        [000000004b0a33690000000001c304c6] => Array
            (
                [name] => New Project
            )

    )
    Array
    (
        [000000004b0a33660000000001c304c6] => Array
            (
                [changes] => 0
                [notes] => Array
                    (
                    )

                [name] => Manager
                [salary] => 100000
                [started] => MongoDB\Driver\BSON\UTCDateTime Object
                    (
                        [sec] => 1275265048
                        [usec] => 0
                    )

                [projects] => Array
                    (
                        [0] => Array
                            (
                                [$ref] => projects
                                [$id] => 4c0300188ead0e947a000000
                                [$db] => my_db
                            )

                    )

            )

    )
    Array
    (
        [000000004b0a336a0000000001c304c6] => Array
            (
                [changes] => 0
                [notes] => Array
                    (
                    )

                [name] => Employee
                [salary] => 50000
                [started] => MongoDB\Driver\BSON\UTCDateTime Object
                    (
                        [sec] => 1275265048
                        [usec] => 0
                    )

                [address] => Array
                    (
                        [address] => 555 Doctrine Rd.
                        [city] => Nashville
                        [state] => TN
                        [zipcode] => 37209
                    )

            )

    )

If we update a property and call ``->flush()`` again we'll get an
efficient update query using the atomic operators:

.. code-block:: php

    <?php
    $newProject = new Project('Another Project');
    $manager->setSalary(200000);
    $manager->addNote('Gave user 100k a year raise');
    $manager->incrementChanges(2);
    $manager->addProject($newProject);

    $dm->persist($newProject);
    $dm->flush();

The above could would produce an update that looks something like
this:

::

    Array
    (
        [$inc] => Array
            (
                [changes] => 2
            )

        [$push] => Array
            (
                [notes] => Array
                    (
                        [$each] => Array
                            (
                                [0] => Gave user 100k a year raise
                            )

                    )

                [projects] => Array
                    (
                        [$each] => Array
                            (
                                [0] => Array
                                    (
                                        [$ref] => projects
                                        [$id] => 4c0310718ead0e767e030000
                                        [$db] => my_db
                                    )

                            )

                    )

            )

        [$set] => Array
            (
                [salary] => 200000
            )

    )

This is a simple example, but it demonstrates well that you can
transparently persist PHP objects while still utilizing the
atomic operators for updating documents! Continue reading to learn
how to get the Doctrine MongoDB Object Document Mapper setup and
running!

Setup
-----

Before we can begin, we'll need to install the Doctrine MongoDB ODM library and
its dependencies. The easiest way to do this is with `Composer`_:

.. code-block:: console

    $ composer require "doctrine/mongodb-odm"

Once ODM and its dependencies have been downloaded, we can begin by creating a
``bootstrap.php`` file in our project's root directory, where Composer's
``vendor/`` directory also resides. Let's start by importing some of the classes
we'll use:

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Configuration;
    use Doctrine\ODM\MongoDB\DocumentManager;
    use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

The first bit of code will be to import Composer's autoloader, so these classes
can actually be loaded:

.. code-block:: php

    <?php

    // ...

    if ( ! file_exists($file = __DIR__.'/vendor/autoload.php')) {
        throw new RuntimeException('Install dependencies to run this script.');
    }

    $loader = require_once $file;

Note that instead of simply requiring the file, we assign its return value to
the ``$loader`` variable. Assuming document classes will be stored in the
``Documents/`` directory (with a namespace to match), we can register them with
the autoloader like so:

.. code-block:: php

    <?php

    // ...

    $loader->add('Documents', __DIR__);

Ultimately, our application will utilize ODM through its ``DocumentManager``
class. Before we can instantiate a ``DocumentManager``, we need to construct the
``Configuration`` object required by its factory method:

.. code-block:: php

    <?php

    // ...

    $config = new Configuration();

Next, we'll specify some essential configuration options. The following assumes
that we will store generated proxy and hydrator classes in the ``Proxies/`` and
``Hydrators/`` directories, respectively. Additionally, we'll define a default
database name to use for document classes that do not specify a database in
their mapping.

.. code-block:: php

    <?php

    // ...

    $config->setProxyDir(__DIR__ . '/Proxies');
    $config->setProxyNamespace('Proxies');
    $config->setHydratorDir(__DIR__ . '/Hydrators');
    $config->setHydratorNamespace('Hydrators');
    $config->setDefaultDB('doctrine_odm');

    spl_autoload_register($config->getProxyManagerConfiguration()->getProxyAutoloader());

.. note::

    The last call to ``spl_autoload_register`` is necessary to autoload generated
    proxy classes. Without this, the proxy library would re-generate proxy
    classes for every request. See the `tuning for production`_ chapter in
    ProxyManager's documentation.

The easiest way to define mappings for our document classes is with annotations.
We'll need to specify an annotation driver in our configuration (with one or
more paths) and register the annotations for the driver:

.. code-block:: php

    <?php

    use Doctrine\Common\Annotations\AnnotationRegistry;

    // ...

    $config->setMetadataDriverImpl(AnnotationDriver::create(__DIR__ . '/Documents'));

    $loader = require_once('path/to/vendor/autoload.php');

    AnnotationRegistry::registerLoader([$loader, 'loadClass']);

At this point, we have everything necessary to construct a ``DocumentManager``:

.. code-block:: php

    <?php

    // ...

    $dm = DocumentManager::create(null, $config);

The final ``bootstrap.php`` file should look like this:

.. code-block:: php

    <?php

    use Doctrine\Common\Annotations\AnnotationRegistry;
    use Doctrine\ODM\MongoDB\Configuration;
    use Doctrine\ODM\MongoDB\DocumentManager;
    use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

    if ( ! file_exists($file = __DIR__.'/vendor/autoload.php')) {
        throw new RuntimeException('Install dependencies to run this script.');
    }

    $loader = require_once $file;
    $loader->add('Documents', __DIR__);

    AnnotationRegistry::registerLoader([$loader, 'loadClass']);

    $config = new Configuration();
    $config->setProxyDir(__DIR__ . '/Proxies');
    $config->setProxyNamespace('Proxies');
    $config->setHydratorDir(__DIR__ . '/Hydrators');
    $config->setHydratorNamespace('Hydrators');
    $config->setDefaultDB('doctrine_odm');
    $config->setMetadataDriverImpl(AnnotationDriver::create(__DIR__ . '/Documents'));

    $dm = DocumentManager::create(null, $config);

That is it! Your ``DocumentManager`` instance is ready to be used!

Providing a custom client
-------------------------

Passing ``null`` to the factory method as first argument tells the document
manager to create a new MongoDB client instance with the appropriate typemap.
If you want to pass custom options (e.g. SSL options, authentication options) to
the client, you'll have to create it yourself manually:

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Configuration;
    use Doctrine\ODM\MongoDB\DocumentManager;
    use MongoDB\Client;

    $client = new Client('mongodb://127.0.0.1', [], ['typeMap' => DocumentManager::CLIENT_TYPEMAP]);
    $config = new Configuration();

    // ...

    $dm = DocumentManager::create($client, $config);

Please note the ``typeMap`` option. This is necessary so ODM can appropriately
handle the results. If you need the client elsewhere with a different typeMap,
please create separate clients for your application and ODM.

.. _MongoDB: https://www.mongodb.com/
.. _10gen: http://www.10gen.com
.. _Composer: http://getcomposer.org/
.. _tuning for production: https://ocramius.github.io/ProxyManager/docs/tuning-for-production.html
