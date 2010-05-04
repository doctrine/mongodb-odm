# Experimental Doctrine MongoDB Object Mapper

The Doctrine\ODM\Mongo namespace is an experimental project for a PHP 5.3 
MongoDB Object Mapper. It allows you to easily write PHP 5 classes and map them
to collections in MongoDB. You just work with your objects like normal and Doctrine
will transparently persist them to Mongo.

This project implements the same "style" of the Doctrine 2 ORM project interface 
so it will look very familiar to you and it has lots of the same features and 
implementations.

Features:

* Transparent document persistence.
* Map one or many embedded documents.
* Map one or many referenced documents.
* Create references between documents in different databases.
* Map documents with Annotations, XML, YAML or plain old PHP code.
* Documents can be stored on the [MongoGridFS](http://www.php.net/MongoGridFS).
* Collection per class(concrete) and single collection inheritance supported.
* Map your Doctrine 2 ORM Entities to the ODM and use mixed data stores.
* Inserts are performed using [MongoCollection::batchInsert()](http://us.php.net/manual/en/mongocollection.batchinsert.php)
* Updates are performed using $set instead of saving the entire document.

## Setup

The setup for the ODM is very similar to that of Doctrine 2 ORM. You'll quickly
notice that the "style" of the ODM is identical to the ORM.

    require '/path/to/doctrine/lib/Doctrine/Common/ClassLoader.php';

    use Doctrine\Common\ClassLoader,
        Doctrine\Common\Annotations\AnnotationReader,
        Doctrine\ODM\MongoDB\DocumentManager,
        Doctrine\ODM\MongoDB\Mongo,
        Doctrine\ODM\MongoDB\Configuration,
        Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

    $classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
    $classLoader->register();

    $classLoader = new ClassLoader('Doctrine', '/path/to/doctrine/lib');
    $classLoader->register();

    $classLoader = new ClassLoader('Documents', __DIR__);
    $classLoader->register();

    $config = new Configuration();
    $config->setProxyDir(__DIR__ . '/Proxies');
    $config->setProxyNamespace('Proxies');

    $reader = new AnnotationReader();
    $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
    $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));

    $dm = DocumentManager::create(new Mongo(), $config);

## Defining Documents

Now you are ready to start defining PHP 5.3 classes and persisting them to MongoDB:

    namespace Documents;

    /**
     * @Document(
     *   db="my_database",
     *   collection="users",
     *   indexes={
     *     @Index(keys={"username"="desc"}, options={"unique"=true})
     *   }
     * )
     */
    class User
    {
        /** @Id
        private $id;

        /** @Field */
        private $username;

        /** @Field */
        private $password;

        /** @ReferenceOne(targetDocument="Account") */
        private $account;

        /** @EmbedOne(targetDocument="Profile")
        private $profile;

        public function getId()
        {
            return $this->id;
        }

        public function setUsername($username)
        {
            $this->username = $username;
        }

        public function getUsername()
        {
            return $this->username;
        }

        public function setPassword($password)
        {
            $this->password = $password;
        }

        public function getPassword()
        {
            return $this->password;
        }

        public function getAccount()
        {
            return $this->account;
        }

        public function setAccount(Account $account)
        {
            $this->account = $account;
        }

        public function getProfile()
        {
            return $this->profile;
        }

        public function setProfile(Profile $profile)
        {
            $this->profile = $profile;
        }
    }

    // Not mapped since it's only embedded in User
    class Profile
    {
        private $firstName;
        private $lastName;

        public function getFirstName()
        {
            return $this->firstName;
        }

        public function setFirstName($firstName)
        {
            $this->firstName = $firstName;
        }

        public function getLastName()
        {
            return $this->lastName;
        }

        public function setLastName($lastName)
        {
            $this->lastName = $lastName;
        }
    }

    /** @Document(db="my_database", collection="accounts") */
    class Account
    {
        /** @Id
        private $id;

        /** @Field */
        private $name;

        public function getId()
        {
            return $this->id;
        }

        public function getName()
        {
            return $this->name;
        }

        public function setName($name)
        {
            $this->name = $name;
        }
    }

## Inheritance Mapping

If you want to take advantage of inheritance you will need to specify some 
mapping information for your documents:

### Single Collection Inheritance

Each Document is stored in a single collection where a discriminator field is
automatically populated to keep track of what classes created each document
in the database:

    namespace Documents;

    /**
     * @Document
     * @InheritanceType("SINGLE_COLLECTION")
     * @DiscriminatorField(fieldName="type")
     * @DiscriminatorMap({"person"="Person", "employee"="Employee"})
     */
    class Person
    {
        // ...
    }

    /**
     * @Document
     */
    class Employee extends Person
    {
        // ...
    }

### Collection Per Class Inheritance

Each Document is stored in its own collection:

    namespace Documents;
    
    /**
     * @Document
     * @InheritanceType("COLLECTION_PER_CLASS")
     * @DiscriminatorMap({"person"="Person", "employee"="Employee"})
     */
    class Person
    {
        // ...
    }

    /**
     * @Document
     */
    class Employee extends Person
    {
        // ...
    }
    
## Persisting Documents

Create a new instance, set some of the properties and persist it:

    $user = new User();
    $user->username = 'jwage';
    $user->password = 'changeme';

    $user->profile = new Profile();
    $user->profile->firstName = 'Jonathan';
    $user->profile->lastName = 'Wage';

    $user->account = new Account();
    $user->account->name = 'Test Account';

    $dm->persist($user);
    $dm->flush();

## Querying for Documents

You can query MongoDB by creating and building a new query with the createQuery()
method or you can directly use the traditional find() and findOne() methods directly.

### Query Object

Here is an example where we use the createQuery() method to create and build a new query:

    $query = $dm->createQuery('User')
        ->where('username', 'jwage');

    $user = $query->getSingleResult();

The where functionality can search within embedded documents properties:

    $query = $dm->createQuery('User')
        ->where('profile.lastName', 'Wage');

    $users = $query->execute();

You can limit which fields are selected with the select() method. Here we only
select the username:

    $query = $dm->createQuery('User')
        ->select('username');
    
    $users = $query->execute();

If you want to just find an document by its identifier you can use the find()
method:

    $user = $dm->find('User', 'the_string_id');

The references for a document are lazily loaded via proxy and collection classes.

    $profile = $user->getProfile(); // proxy and no query to database has been made yet
    
    echo $profile->getName(); // queries the database and lazily loads the document

It works the same way for a collection of references. It will lazily fetch the
collection with one query initializing all proxies in the collection.

### Traditional MongoDB API

In addition to the Doctrine Query object you can use the traditional MongoDB API
you typically see:

    $user = $dm->findOne('User', array('username' => 'jwage'));

You can search the users collection using the find() method:

    $users = $dm->find('User');
    
    foreach ($users as $user) {
        echo $user->username;
    }

To find by the ID using findOne():

    $user = $dm->findOne('User', array('_id' => new MongoId('the_string_id')));

## Storing Files

The PHP Mongo extension provides a nice and convenient way to store files in chunks
of data with the [MongoGridFS](http://us.php.net/manual/en/class.mongogridfs.php).

It uses two database collections, one to store the metadata for the file, and another
to store the contents of the file. The contents are stored in chunks to avoid
going over the maximum allowed size of a MongoDB document.

You can easily setup a Document that is stored using the MongoGridFS:

    <?php

    namespace Documents;

    /** @Document */
    class Image
    {
        /** @Id */
        private $id;

        /** @Field */
        private $name;

        /** @File */
        private $file;

        /** @Field */
        private $uploadDate;

        /** @Field */
        private $length;

        /** @Field */
        private $chunkSize;

        /** @Field */
        private $md5;

        private function getId()
        {
            return $id;
        }

        private function setName($name)
        {
            $this->name = $name;
        }

        private function getName()
        {
            return $this->name;
        }

        private function getFile()
        {
            return $this->file;
        }

        private function setFile($file)
        {
            $this->file = $file;
        }
    }

Notice how we annotated the $file property with @File. This is what tells the
Document that it is is to be stored using the MongoGridFS and the MongoGridFSFile
instance is placed in the $file property for you to access the actual file itself.

First you need to create a new Image:

    $image = new Image();
    $image->setName('Test image');
    $image->setFile('/path/to/image.png');

    $dm->persist($image);
    $dm->flush();

Now you can later query for the Image and render it:

    $image = $dm->createQuery('Documents\Image')
        ->where('name', 'Test image')
        ->getSingleResult();

    header('Content-type: image/png;');
    echo $image->getFile()->getBytes();

You can of course make references to this Image document from another document.
Imagine you had a Profile document and you wanted every Profile to have a profile
image:

    namespace Documents;

    <?php

    namespace Documents;

    /** @Document */
    class Profile
    {
        /** @Id */
        private $id;

        /** @Field */
        private $name;

        /** @ReferenceOne(targetDocument="Documents\Image") */
        private $image;

        private function getId()
        {
          return $this->id;
        }

        private function getName()
        {
            return $this->name;
        }

        private function setName($name)
        {
            $this->name = $name;
        }

        private function getImage()
        {
            return $this->image;
        }

        private function setImage(Image $image)
        {
            $this->image = $image;
        }
    }

Now you can create a new Profile and give it an Image:

    $image = new Image();
    $image->setName('Test image');
    $image->setFile('/path/to/image.png');

    $profile = new Profile();
    $profile->setName('Jonathan H. Wage');
    $profile->setImage($image);

    $dm->persist($profile);
    $dm->flush();

If you want to query for the Profile and load the Image reference in a query
you can use:

    $profile = $dm->createQuery('Profile')
        ->where('name', 'Jonathan H. Wage')
        ->getSingleResult();

    $image = $profile->getImage();

    header('Content-type: image/png;');
    echo $image->getFile()->getBytes();

## Logging

If you want to turn on logging and receive information about queries made to the
database you can do so on your Doctrine\ODM\MongoDB\Configuration instance we 
configured at the start of this document:

    // ...

    $config->setLoggerCallable(function(array $log) {
        print_r($log);
    });

You can register any PHP callable and it will be notified with a single argument
that is an array of information about the query being sent to the database.