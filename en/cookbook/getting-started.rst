Getting Started
===============

Doctrine 2 is a project that aims to handle the persistence of the
domain model in a non-interfering way. Non-relational or no-sql
databases like MongoDB give you flexibility of building data store
around your object model and not vise versa. You can read more on the
initial configuration and setup in `Introduction to MongoDB Object
Document Mapper`_. This section will give you a basic overview of what
could be accomplished using Doctrine2 ODM.

Example Model: Simple Blog
--------------------------

To create the simplest example, let’s assume the following:

-  Blog has a user.
-  Blog user can make blog posts

A first prototype
-----------------

For the above mentioned example, something as simple as this could be
modeled:

::

    [php]
    class User
    {
        public $name;
        public $email;
        public $posts = array();

    }

    class BlogPost
    {
        public $title;
        public $body;
        public $createdAt;
    }

    **CAUTION** This is only a prototype, you should not use public
    properties with Doctrine 2 at all. You should always use private or
    protected properties and access them using public getters and
    setters.

Persistent Models
-----------------

To make the above classes persistent, all we need to do is to add some
annotations and identifiers around them:

::

    [php]
    /**
     * @Document(db="test_database", collection="users")
     */
    class User
    {
        /**
         * @Id
         */
        public $id;
        /**
         * @String
         */
        public $name;
        /**
         * @String
         */
        public $email;
        /**
         * @ReferenceMany(targetDocument="BlogPost", cascade="all")
         */
        public $posts = array();

    }

    /**
     * @Document(db="test_database", collection="blog_posts")
     */
    class BlogPost
    {
        /**
         * @Id
         */
        public $id;
        /**
         * @String
         */
        public $title;
        /**
         * @String
         */
        public $body;
        /**
         * @Date
         */
        public $createdAt;
    }

That’s it, we have our models, and we can save and retreive them. Now
all we need to do is to properly instantiate the DocumentManager
instance(`Introduction to MongoDB Object Document Mapper`_):

::

    [php]
    $config = new Configuration();
    $config->setProxyDir('/path/to/generate/proxies');
    $config->setProxyNamespace('Proxies');

    $reader = new AnnotationReader();
    $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
    $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));

    $dm = DocumentManager::create(new Mongo(), $config);

Usage
-----

Here is how you would use your models now:

::

    [php]
    // ...
    // create user
    $user = new User();
    $user->name = 'Bulat S.';
    $user->email = 'email@example.com';

    // tell Doctrine 2 to save $user on the next flush()
    $dm->persist($user);

    // create blog post
    $post = new BlogPost();
    $post->title = 'My First Blog Post';
    $post->body = 'MongoDB + Doctrine 2 ODM = awesomeness!';
    $post->createdAt = date('Y-m-d');

    // calling $dm->persist($post) is not necessary, since $user will "cascade" $user->posts changes
    $user->posts[] = $post;

    // store everything to MongoDB
    $dm->flush();

Now if you did everything correctly, you should have those two objects
stored in MongoDB in correct collections and databases. You can use the
`php-mongodb-admin project, hosted on github`_ to look at your
‘blog\_posts’ collection, where you will see only one document:

::

    Array
    (
        [_id] => 4bec5869fdc212081d000000
        [title] => My First Blog Post
        [body] => MongoDB + Doctrine 2 ODM = awesomeness!
        [createdAt] => MongoDate Object
            (
                [sec] => 1273723200
                [usec] => 0
            )
    )

And you ‘users’ collection would consist of the following:

::

    Array
    (
        [_id] => 4bec5869fdc212081d010000
        [name] => Bulat S.
        [email] => email@example.com
        [posts] => Array
            (
                [0] => Array
                    (
                        [$ref] => blog_posts
                        [$id] => 4bec5869fdc212081d000000
                        [$db] => test_database
                    )
            )
    )

To retreive the user, you will need its ID:

::

    [php]
    // ...
    $userId = $user->id;

Loading the model, when you know id is easy:

::

    [php]
    // ...
    $loadedUser = $dm->find('User', $userId);

    foreach ($loadedUser->posts as $post) {
        echo $

TRUNCATED! Please download pandoc if you want to convert large files.

.. _Introduction to MongoDB Object Document
Mapper: /projects/mongodb_odm/1.0/docs/reference/introduction/en#introduction
.. _php-mongodb-admin project, hosted on
github: http://github.com/jwage/php-mongodb-admin