# Experimental Doctrine MongoDB Object Mapper

The Doctrine\ODM\Mongo namespace is an experimental project for a PHP 5.3 
MongoDB Object Mapper.

## Setup

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

    $reader = new AnnotationReader();
    $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\Driver\\');
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
        public $id;

        /** @Field */
        public $username;

        /** @Field */
        public $password;

        /** @ReferenceOne(targetDocument="Account") */
        public $account;

        /** @EmbedOne(targetDocument="Profile")
        public $profile;
    }

    // Not mapped since it's only embedded in User
    class Profile
    {
        public $firstName;
        public $lastName;
    }

    /** @Document(db="my_database", collection="accounts") */
    class Account
    {
        /** @Id
        public $id;

        /** @Field */
        public $name;
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

If you want to just find an document by its identifier you can use the findByID()
method:

    $user = $dm->findByID('User', 'the_string_id');

You may want to load the associations for an document, you can do this with the 
loadDocumentAssociations() method:

    $dm->loadDocumentAssociations($user);

Now you can access the ->account property and get an Account instance:

    echo $user->account->name; // Test Account

If you only want to load a specific association you can use the loadDocumentAssociation($name)
method:

    $dm->loadDocumentAssociation($user, 'account');

To automatically load the association during hydration you can specify the 
association to load on a query with the loadAssociation() method:

    $query = $dm->createQuery('User')
        ->loadAssociation('account');
    
    $users = $query->execute();
    foreach ($users as $user) {
        echo $user->account->name."\n";
    }

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

        public function getId()
        {
            return $id;
        }

        public function setName($name)
        {
            $this->name = $name;
        }

        public function getName()
        {
            return $this->name;
        }

        public function getFile()
        {
            return $this->file;
        }

        public function setFile($file)
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

You can of course make associations to this Image document from another document.
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

        public function getImage()
        {
            return $this->image;
        }

        public function setImage(Image $image)
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

If you want to query for the Profile and load the Image association in a query
you can use:

    $profile = $dm->createQuery('Profile')
        ->loadAssociation('image')
        ->where('name', 'Jonathan H. Wage')
        ->getSingleResult();

    $image = $profile->getImage();

    header('Content-type: image/png;');
    echo $image->getFile()->getBytes();