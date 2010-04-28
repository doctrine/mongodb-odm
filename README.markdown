# Experimental Doctrine MongoDB Object Mapper

The Doctrine\ODM\Mongo namespace is an experimental project for a PHP 5.3 
MongoDB Object Mapper.

## Setup

    require '/path/to/doctrine/lib/Doctrine/Common/ClassLoader.php';

    use Doctrine\Common\ClassLoader,
        Doctrine\ODM\MongoDB\EntityManager,
        Doctrine\ODM\MongoDB\Mongo;

    $classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
    $classLoader->register();

    $classLoader = new ClassLoader('Doctrine', '/path/to/doctrine/lib');
    $classLoader->register();

    $classLoader = new ClassLoader('Entities', __DIR__);
    $classLoader->register();

    $em = EntityManager::create(new Mongo());

## Defining Entities

Now you are ready to start defining PHP 5.3 classes and persisting them to MongoDB:

    namespace Entities;

    class User
    {
        public $id;
        public $username;
        public $password;
        public $account;
        public $profile;

        public static function loadMetadata($class)
        {
            $class->setDB('my_database');
            $class->setCollection('users');

            // stored as an embedded object on the user document
            $class->mapOneEmbedded(array(
                'fieldName' => 'profile',
                'targetEntity' => 'Profile'
            ));
            // mapManyEmbedded() also exists

            // stored in the accounts collection and the reference is lazily loaded
            $class->mapOneAssociation(array(
                'fieldName' => 'account',
                'targetEntity' => 'Account'
            ));
            // mapManyAssociation() also exists
        }
    }
    
    class Profile
    {
        public $firstName;
        public $lastName;
    }

    class Account
    {
        public $id;
        public $name;

        public static function loadMetadata($class)
        {
            $class->setDB('my_database');
            $class->setCollection('accounts');
        }
    }

## Persisting Entities

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

## Querying for Entities

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

If you want to just find an entity by its identifier you can use the findByID()
method:

    $user = $em->findByID('User', 'the_string_id');

You may want to load the associations for an entity, you can do this with the 
loadEntityAssociations() method:

    $em->loadEntityAssociations($user);

Now you can access the ->account property and get an Account instance:

    echo $user->account->name; // Test Account

If you only want to load a specific association you can use the loadEntityAssociation($name)
method:

    $em->loadEntityAssociation($user, 'account');

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