# Experimental Doctrine MongoDB Object Mapper

The `Doctrine\ODM\Mongo` namespace is an experimental project for a PHP 5.3 
MongoDB Object Mapper.

## Setup

    require '/path/to/doctrine/lib/Doctrine/Common/ClassLoader.php';

    use Doctrine\Common\ClassLoader,
        Doctrine\ODM\MongoDB\EntityManager;

    $classLoader = new ClassLoader('Doctrine\ODM', __DIR__ . '/../lib');
    $classLoader->register();

    $classLoader = new ClassLoader('Entities', __DIR__);
    $classLoader->register();

    $em = new EntityManager(new Mongo());

Now you are ready to start defining PHP 5.3 classes and persisting them to MongoDB!

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

If you want to query for this model you can easily create a new query:

    $query = $em->createQuery('User')
        ->where('username', 'jwage');
    
    $user = $query->getSingleResult();

You can search inside an embedded document:

    $query = $em->createQuery('User')
        ->where('profile.lastName', 'Wage');

    $users = $query->execute();

If you want to load an association in a query you can use the `loadAssociation()` 
method:

    $query = $em->createQuery('User')
        ->loadAssociation('account');
    
    $users = $query->execute();
    foreach ($users as $user) {
        echo $user->account->name."\n";
    }