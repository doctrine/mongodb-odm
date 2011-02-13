Doctrine MongoDB Object Document Mapper is built for PHP 5.3.0+ and
provides transparent persistence for PHP objects.

Features Overview
-----------------


-  Transparent persistence.
-  Map one or many embedded documents.
-  Map one or many referenced documents.
-  Create references between documents in different databases.
-  Map documents with Annotations, XML, YAML or plain old PHP code.
- 
   Documents can be stored on the
   `MongoGridFS <http://www.php.net/MongoGridFS>`_.
- 
   Collection per class(concrete) and single collection inheritance
   supported.
- 
   Map your Doctrine 2 ORM Entities to the ODM and use mixed data
   stores.
- 
   Inserts are performed using
   `MongoCollection::batchInsert() <http://us.php.net/manual/en/mongocollection.batchinsert.php>`_
-  Updates are performed using atomic operators.

Here is a quick example of some PHP object documents that
demonstrates a few of the features:

::

    <?php
    /** @MappedSuperclass */
    abstract class BaseEmployee
    {
        /** @Id */
        protected $id;
    
        /** @Increment */
        protected $changes = 0;
    
        /** @Collection */
        protected $notes = array();
    
        /** @String */
        protected $name;
    
        /** @Float */
        protected $salary;
    
        /** @Date */
        protected $started;
    
        /** @Date */
        protected $left;
    
        /** @EmbedOne(targetDocument="Address") */
        protected $address;
    
        // ...
    }
    
    /** @Document(db="my_db", collection="employees") */
    class Employee extends BaseEmployee
    {
        /** @ReferenceOne(targetDocument="Documents\Manager") */
        private $manager;
    
        // ...
    }
    
    /** @Document(db="my_db", collection="managers") */
    class Manager extends BaseEmployee
    {
        /** @ReferenceMany(targetDocument="Documents\Project") */
        private $projects = array();
    
        // ...
    }
    
    /** @EmbeddedDocument */
    class Address
    {
        /** @String */
        private $address;
    
        /** @String */
        private $city;
    
        /** @String */
        private $state;
    
        /** @String */
        private $zipcode;
    
        // ...
    }
    
    /** @Document(db="my_db", collection="projects") */
    class Project
    {
        /** @Id */
        private $id;
    
        /** @String */
        private $name;
    
        public function __construct($name)
        {
            $this->name = $name;
        }
    
        // ...
    }

Now those objects can be used just like you weren't using any
persistence layer at all and can be persisted transparently by
Doctrine:

::

    <?php
    $employee = new Employee();
    $employee->setName('Employee');
    $employee->setSalary(50000.00);
    $employee->setStarted(new \DateTime());
    
    $address = new Address();
    $address->setAddress('555 Doctrine Rd.');
    $address->setCity('Nashville');
    $address->setState('TN');
    $address->setZipcode('37209');
    $employee->setAddress($address);
    
    $project = new Project('New Project');
    $manager = new Manager();
    $manager->setName('Manager');
    $manager->setSalary(100000.00);
    $manager->setStarted(new \DateTime());
    $manager->addProject($project);
    
    $dm->persist($employee);
    $dm->persist($address);
    $dm->persist($project);
    $dm->persist($manager);
    $dm->flush();

The above would batch insert the following:

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
                [started] => MongoDate Object
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
                [started] => MongoDate Object
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

::

    <?php
    $newProject = new Project('Another Project');
    $manager->setSalary(200000.00);
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
    
        [$pushAll] => Array
            (
                [notes] => Array
                    (
                        [0] => Gave user 100k a year raise
                    )
    
                [projects] => Array
                    (
                        [0] => Array
                            (
                                [$ref] => projects
                                [$id] => 4c0310718ead0e767e030000
                                [$db] => my_db
                            )
    
                    )
    
            )
    
        [$set] => Array
            (
                [salary] => 200000
            )
    
    )

This is a simple example but it demonstrates well that you can
transparently persist PHP objects while still utilizing the the
atomic operators for updating documents! Continue reading to learn
how to get the Doctrine MongoDB Object Document Mapper setup and
running!

Setup
-----

Before we can begin setting up the code we need to download the
Doctrine MongoDB package. You can learn about how to download the
code
`here <http://www.doctrine-project.org/projects/mongodb_odm/download>`_.
The easiest way is to just clone it using git:

::

    $ git clone git://github.com/doctrine/mongodb-odm.git mongodb_odm
    $ git submodule init
    $ git submodule update

Now that we have the code, we can begin our setup. First in your
bootstrap file you need to require the ``ClassLoader`` from the
``Doctrine\Common`` namespace which is included in the vendor
libraries:

::

    <?php
    require 'mongodb_odm/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';

At the top of your bootstrap file you need to tell PHP which
namespaces you want to use:

::

    <?php
    // ...
    
    use Doctrine\Common\ClassLoader,
        Doctrine\Common\Annotations\AnnotationReader,
        Doctrine\ODM\MongoDB\DocumentManager,
        Doctrine\MongoDB\Connection,
        Doctrine\ODM\MongoDB\Configuration,
        Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

Next we need to setup the ``ClassLoader`` instances for all of the
classes we need to autoload:

::

    <?php
    // ...
    
    // ODM Classes
    $classLoader = new ClassLoader('Doctrine\ODM', 'mongodb_odm/lib');
    $classLoader->register();
    
    // Common Classes
    $classLoader = new ClassLoader('Doctrine\Common', 'mongodb_odm/lib/vendor/doctrine-common/lib');
    $classLoader->register();
    
    // MongoDB Classes
    $classLoader = new ClassLoader('Doctrine\MongoDB', 'mongodb_odm/lib/vendor/doctrine-mongodb/lib');
    $classLoader->register();
    
    // Document classes
    $classLoader = new ClassLoader('Documents', __DIR__);
    $classLoader->register();

Now we can configure the ODM and create our ``DocumentManager``
instance:

::

    <?php
    // ...
    
    $config = new Configuration();
    $config->setProxyDir('/path/to/generate/proxies');
    $config->setProxyNamespace('Proxies');
    
    $config->setHydratorDir(__DIR__ . '/path/to/generate/hydrators');
    $config->setHydratorNamespace('Hydrators');
    
    $reader = new AnnotationReader();
    $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
    $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));
    
    $dm = DocumentManager::create(new Mongo(), $config);

Your final bootstrap code should look like the following:

::

    <?php
    // bootstrap.php
    
    require 'mongodb_odm/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php';
    
    use Doctrine\Common\ClassLoader,
        Doctrine\Common\Annotations\AnnotationReader,
        Doctrine\ODM\MongoDB\DocumentManager,
        Doctrine\MongoDB\Connection,
        Doctrine\ODM\MongoDB\Configuration,
        Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
    
    // ODM Classes
    $classLoader = new ClassLoader('Doctrine\ODM', 'mongodb_odm/lib');
    $classLoader->register();
    
    // Common Classes
    $classLoader = new ClassLoader('Doctrine\Common', 'mongodb_odm/lib/vendor/doctrine-common/lib');
    $classLoader->register();
    
    // Document classes
    $classLoader = new ClassLoader('Documents', __DIR__);
    $classLoader->register();
    
    $config = new Configuration();
    $config->setProxyDir('/path/to/generate/proxies');
    $config->setProxyNamespace('Proxies');
    
    $config->setHydratorDir(__DIR__ . '/path/to/generate/hydrators');
    $config->setHydratorNamespace('Hydrators');
    
    $reader = new AnnotationReader();
    $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
    $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));
    
    $dm = DocumentManager::create(new Mongo(), $config);

That is it! Your ``DocumentManager`` instance is ready to be used!


