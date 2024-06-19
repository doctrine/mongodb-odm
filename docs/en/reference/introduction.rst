Introduction
============

What is Doctrine MongoDB ODM?
-----------------------------

Doctrine MongoDB ODM (Object Document Mapper) is a PHP 8.1+ library that provides
an abstraction layer to work with `MongoDB`_ documents in PHP applications.
It allows developers to map PHP objects to MongoDB documents, enabling an
intuitive and structured approach to handling data within a MongoDB database.

Features Overview
-----------------

-  **Object Mapping**: Map PHP objects to MongoDB documents, with support for
   embedded and referenced documents. Mapping can be configured using PHP
   attributes, XML, or PHP code.
-  **Atomic Updates**: Utilizes MongoDB's atomic operators for saving changes to
   documents, ensuring data integrity and consistency.
-  **Schema Management**: Facilitates schema design and management, ensuring
   that your MongoDB collections adhere to your data model, their indexes, and
   validation rules.
-  **Query Builder**: Provides a fluent and flexible query builder that
   simplifies the creation of simple and complex queries.
-  **Aggregation Framework**: Supports MongoDB's powerful aggregation framework
   for advanced data processing and analysis.
-  **Repositories**: Offers a repository pattern for encapsulating data access
   logic and promotes code reusability and separation of concerns.
-  **Events System**: Leverages an events system that allows you to hook into
   various stages of the document lifecycle for custom behavior.
-  **GridFS Support**: Stores large files and binary data in GridFS buckets.
-  **Doctrine ORM Integration**: Seamlessly integrates with Doctrine ORM to
   allow you to map your ORM entities to the ODM, and use different databases.

Example
-------

Here is a quick example of some PHP object documents that demonstrates a few of
the features.

.. code-block:: php

    <?php

    use Doctrine\Common\Collections\ArrayCollection;
    use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
    use DateTime;

    #[ODM\MappedSuperclass]
    abstract class BaseEmployee
    {
        #[ODM\Id]
        private $id;

        #[ODM\Field(type: 'int', strategy: 'increment')]
        private $changes = 0;

        #[ODM\Field(type: 'collection')]
        private $notes = [];

        #[ODM\Field(type: 'string')]
        private $name;

        #[ODM\Field(type: 'int')]
        private $salary;

        #[ODM\Field(type: 'date')]
        private $started;

        #[ODM\Field(type: 'date')]
        private $left;

        #[ODM\EmbedOne(targetDocument: Address::class)]
        private $address;

        public function getId(): ?string { return $this->id; }

        public function getChanges(): int { return $this->changes; }
        public function incrementChanges(): void { $this->changes++; }

        public function getNotes(): array { return $this->notes; }
        public function addNote($note) { $this->notes[] = $note; }

        public function getName(): ?string { return $this->name; }
        public function setName(string $name): void { $this->name = $name; }

        public function getSalary(): ?int { return $this->salary; }
        public function setSalary(int $salary): void { $this->salary = $salary; }

        public function getStarted(): ?DateTime { return $this->started; }
        public function setStarted(DateTime $started) { $this->started = $started; }

        public function getLeft(): ?DateTime { return $this->left; }
        public function setLeft(DateTime $left) { $this->left = $left; }

        public function getAddress(): ?Address { return $this->address; }
        public function setAddress(Address $address): void { $this->address = $address; }
    }

    #[ODM\Document]
    class Employee extends BaseEmployee
    {
        #[ODM\ReferenceOne(targetDocument: Manager::class)]
        private $manager;

        public function getManager(): ?Manager { return $this->manager; }
        public function setManager(Manager $manager): void { $this->manager = $manager; }
    }

    #[ODM\Document]
    class Manager extends BaseEmployee
    {
        #[ODM\ReferenceMany(targetDocument: Project::class)]
        private $projects;

        public function __construct() { $this->projects = new ArrayCollection(); }

        public function getProjects(): Collection { return $this->projects; }
        public function addProject(Project $project): void { $this->projects[] = $project; }
    }

    #[ODM\EmbeddedDocument]
    class Address
    {
        #[ODM\Field(type: 'string')]
        private $address;

        #[ODM\Field(type: 'string')]
        private $city;

        #[ODM\Field(type: 'string')]
        private $state;

        #[ODM\Field(type: 'string')]
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

    #[ODM\Document]
    class Project
    {
        #[ODM\Id]
        private $id;

        #[ODM\Field(type: 'string')]
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

    /** @var Doctrine\ODM\MongoDB\DocumentManager $dm */
    $dm->persist($employee);
    $dm->persist($address);
    $dm->persist($project);
    $dm->persist($manager);
    $dm->flush();

The above would insert the following documents into MongoDB collections:

::

    // Project collection
    {
        _id: ObjectId("..2"),
        name: "New Project"
    }

    // Manager collection
    {
        _id: ObjectId("..3"),
        changes: 0,
        notes: [],
        name: "Manager",
        salary: 100000,
        started: Date("2024-06-19T14:30:52.557Z"),
        projects: [
            {
                $ref: "Project",
                $id: ObjectId("..2")
            }
        ]
    }

    // Employee collection
    {
        _id: ObjectId("..1"),
        changes: 0,
        notes: [],
        name: "Employee",
        salary: 50000,
        started: Date("2024-06-19T14:30:52.557Z"),
        address: {
            address: "555 Doctrine Rd.",
            city: "Nashville",
            state: "TN",
            zipcode: "37209"
        }
    }


If we update a property and call ``->flush()`` again we'll get an
efficient update query using the atomic operators:

.. code-block:: php

    <?php
    $newProject = new Project('Another Project');
    $manager->setSalary(200000);
    $manager->addNote('Gave user 100k a year raise');
    $manager->incrementChanges();
    $manager->addProject($newProject);

    $dm->persist($newProject);
    $dm->flush();

The above could would produce an update to Manager's collection that looks
something like this:

::

    {
        $inc: { changes: 1 },
        $push: {
            projects: {
                $each: [
                    {
                        $ref: "Project",
                        $id: ObjectId("..5")
                    }
                ]
            }
        },
        $set: {
            notes: [
                "Gave user 100k a year raise"
            ],
            salary: 200000
        },
    }

This is a simple example, but it demonstrates well that you can
transparently persist PHP objects while still utilizing the
atomic operators for updating documents! Continue reading to learn
how to get the Doctrine MongoDB Object Document Mapper setup and
running!

Setup
-----

A prerequisite of using the Doctrine MongoDB ODM library is to have the
MongoDB PHP extension installed and enabled. See the `official PHP
manual`_ for download and installation instructions.

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
    use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;

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

The easiest way to define mappings for our document classes is with attributes.
We'll need to specify an attribute driver in our configuration (with one or
more paths) and register the attributes for the driver:

.. code-block:: php

    <?php

    // ...

    $config->setMetadataDriverImpl(AttributeDriver::create(__DIR__ . '/Documents'));

At this point, we have everything necessary to construct a ``DocumentManager``:

.. code-block:: php

    <?php

    // ...

    $dm = DocumentManager::create(null, $config);

The final ``bootstrap.php`` file should look like this:

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Configuration;
    use Doctrine\ODM\MongoDB\DocumentManager;
    use Doctrine\ODM\MongoDB\Mapping\Driver\AttributeDriver;

    if ( ! file_exists($file = __DIR__.'/vendor/autoload.php')) {
        throw new RuntimeException('Install dependencies to run this script.');
    }

    require_once $file;

    $config = new Configuration();
    $config->setProxyDir(__DIR__ . '/Proxies');
    $config->setProxyNamespace('Proxies');
    $config->setHydratorDir(__DIR__ . '/Hydrators');
    $config->setHydratorNamespace('Hydrators');
    $config->setDefaultDB('doctrine_odm');
    $config->setMetadataDriverImpl(AttributeDriver::create(__DIR__ . '/Documents'));

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
.. _Composer: http://getcomposer.org/
.. _tuning for production: https://ocramius.github.io/ProxyManager/docs/tuning-for-production.html
.. _official PHP manual: https://www.php.net/manual/en/mongodb.installation.php
