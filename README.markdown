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

    $em = DocumentManager::create(new Mongo(), $config);

## Defining Documents

Now you are ready to start defining PHP 5.3 classes and persisting them to MongoDB:

    namespace Documents;

    /** @Document(db="my_database", collection="users") */
    class User
    {
        /** @Field(id="true")
        public $id;

        /** @Field */
        public $username;

        /** @Field */
        public $password;

        /** @Field(reference="true", type="one", targetDocument="Account") */
        public $account;

        /** @Field(embedded="true", type="one", targetDocument="Profile")
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
        /** @Field(id="true")
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

    $em->persist($user);
    $em->flush();

## Querying for Documents

You can query MongoDB by creating and building a new query with the createQuery()
method or you can directly use the traditional find() and findOne() methods directly.

### Query Object

Here is an example where we use the createQuery() method to create and build a new query:

    $query = $em->createQuery('User')
        ->where('username', 'jwage');

    $user = $query->getSingleResult();

The where functionality can search within embedded documents properties:

    $query = $em->createQuery('User')
        ->where('profile.lastName', 'Wage');

    $users = $query->execute();

You can limit which fields are selected with the select() method. Here we only
select the username:

    $query = $em->createQuery('User')
        ->select('username');
    
    $users = $query->execute();

If you want to just find an document by its identifier you can use the findByID()
method:

    $user = $em->findByID('User', 'the_string_id');

You may want to load the associations for an document, you can do this with the 
loadDocumentAssociations() method:

    $em->loadDocumentAssociations($user);

Now you can access the ->account property and get an Account instance:

    echo $user->account->name; // Test Account

If you only want to load a specific association you can use the loadDocumentAssociation($name)
method:

    $em->loadDocumentAssociation($user, 'account');

To automatically load the association during hydration you can specify the 
association to load on a query with the loadAssociation() method:

    $query = $em->createQuery('User')
        ->loadAssociation('account');
    
    $users = $query->execute();
    foreach ($users as $user) {
        echo $user->account->name."\n";
    }

### Traditional MongoDB API

In addition to the Doctrine Query object you can use the traditional MongoDB API
you typically see:

    $user = $em->findOne('User', array('username' => 'jwage'));

You can search the users collection using the find() method:

    $users = $em->find('User');
    
    foreach ($users as $user) {
        echo $user->username;
    }

To find by the ID using findOne():

    $user = $em->findOne('User', array('_id' => new MongoId('the_string_id')));