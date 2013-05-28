Storing Files with MongoGridFS
==============================

The PHP Mongo extension provides a nice and convenient way to store
files in chunks of data with the
`MongoGridFS <http://us.php.net/manual/en/class.mongogridfs.php>`_.

It uses two database collections, one to store the metadata for the
file, and another to store the contents of the file. The contents
are stored in chunks to avoid going over the maximum allowed size
of a MongoDB document.

You can easily setup a Document that is stored using the
MongoGridFS:

.. code-block:: php

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
            return $this->id;
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

Notice how we annotated the $file property with @File. This is what
tells the Document that it is to be stored using the MongoGridFS
and the MongoGridFSFile instance is placed in the $file property
for you to access the actual file itself.

The $uploadDate, $chunkSize and $md5 properties are automatically filled in
for each file stored in GridFS (whether you like that or not).
Feel free to create getters in your document to actually make use of them,
but keep in mind that their values will be initially unset for new objects
until the next time the document is hydrated (fetched from the database).

First you need to create a new Image:

.. code-block:: php

    <?php

    $image = new Image();
    $image->setName('Test image');
    $image->setFile('/path/to/image.png');
    
    $dm->persist($image);
    $dm->flush();

Now you can later query for the Image and render it:

.. code-block:: php

    <?php

    $image = $dm->createQueryBuilder('Documents\Image')
        ->field('name')->equals('Test image')
        ->getQuery()
        ->getSingleResult();
    
    header('Content-type: image/png;');
    echo $image->getFile()->getBytes();

You can of course make references to this Image document from
another document. Imagine you had a Profile document and you wanted
every Profile to have a profile image:

.. code-block:: php

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

.. code-block:: php

    <?php

    $image = new Image();
    $image->setName('Test image');
    $image->setFile('/path/to/image.png');
    
    $profile = new Profile();
    $profile->setName('Jonathan H. Wage');
    $profile->setImage($image);
    
    $dm->persist($profile);
    $dm->flush();

If you want to query for the Profile and load the Image reference
in a query you can use:

.. code-block:: php

    <?php

    $profile = $dm->createQueryBuilder('Profile')
        ->field('name')->equals('Jonathan H. Wage')
        ->getQuery()
        ->getSingleResult();
    
    $image = $profile->getImage();
    
    header('Content-type: image/png;');
    echo $image->getFile()->getBytes();
