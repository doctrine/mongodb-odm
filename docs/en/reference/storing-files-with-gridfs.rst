Storing Files with GridFS
=========================

About GridFS
------------

With GridFS, MongoDB provides a specification for storing and retrieving files
that exceed the document size limit of 16 MB. GridFS uses two collections to
store files. One collection stores the file chunks, and the other stores file
metadata. More information on GridFS can be found in the
`MongoDB GridFS documentation <https://docs.mongodb.com/manual/core/gridfs/>`_.

GridFS files provide the following properties
-
    ``_id`` stores the identifier of the file. By default, it uses a BSON
    ObjectId, although you can override this in the mapping.
-
    ``chunkSize`` stores the size of a single chunk in bytes. By default, chunks
    are 261120 bytes (i.e. 255 KiB) in size.
-
    ``filename`` is the name of the file as assigned. Note that filenames don't
    need to be unique: instead, multiple files with the same name are treated
    as revisions of that same file, with the last file uploaded being the latest
    revision.
-
    ``length`` stores the size of the file in bytes.
-
    ``metadata`` is an optional embedded document that can be used to store
    additional data along with the file.
-
    ``uploadDate`` stores the date when the file was originally persisted to the
    GridFS bucket. It is also used to track revisions of multiple files with the
    same filename.

Mapping documents as GridFS files
---------------------------------

.. code-block:: php

    <?php

    namespace Documents;

    use Doctrine\ODM\MongoDB\Mapping\Annotations\File;
    use Doctrine\ODM\MongoDB\Mapping\Annotations\Id;

    #[File(bucketName: 'image')]
    class Image
    {
        #[Id]
        private ?string $id;

        #[File\Filename]
        private ?string $name;

        #[File\UploadDate]
        private \DateTimeInterface $uploadDate;

        #[File\Length]
        private ?int $length;

        #[File\ChunkSize]
        private ?int $chunkSize;

        #[File\Metadata(targetDocument: ImageMetadata::class)]
        private ImageMetadata $metadata;

        public function getId(): ?string
        {
            return $this->id;
        }

        public function getName(): ?string
        {
            return $this->name;
        }

        public function getChunkSize(): ?int
        {
            return $this->chunkSize;
        }

        public function getLength(): ?int
        {
            return $this->length;
        }

        public function getUploadDate(): \DateTimeInterface
        {
            return $this->uploadDate;
        }

        public function getMetadata(): ?ImageMetadata
        {
            return $this->metadata;
        }
    }

If you would rather use XML to map metadata, the corresponding mapping would
look like this:

.. code-block:: xml

    <?xml version="1.0" encoding="UTF-8"?>

    <doctrine-mongo-mapping xmlns="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping
        http://doctrine-project.org/schemas/odm/doctrine-mongo-mapping.xsd">

        <gridfs-file name="Documents\Image">
            <id />
            <length />
            <chunk-size />
            <upload-date />
            <filename field-name="name" />

            <metadata target-document="Documents\ImageMetadata" />
        </gridfs-file>
    </doctrine-mongo-mapping>

With XML mappings, the fields are automatically mapped to camel-cased properties.
To change property names, simply override the ``fieldName`` argument for each
field. You cannot override any other options for GridFS fields.

The ``ImageMetadata`` class must be an embedded document:

.. code-block:: php

    <?php

    namespace Documents;

    use Doctrine\ODM\MongoDB\Mapping\Annotations\EmbeddedDocument;
    use Doctrine\ODM\MongoDB\Mapping\Annotations\Field;

    #[EmbeddedDocument]
    class ImageMetadata
    {
        #[Field(type: 'string')]
        private string $contentType;

        public function __construct(string $contentType)
        {
            $this->contentType = $contentType;
        }

        public function getContentType(): ?string
        {
            return $this->contentType;
        }
    }

Inserting files into GridFS buckets
-----------------------------------

To insert a new file, you have to upload its contents using the repository. You
have the option to upload contents from a file or a stream. Alternatively, you
can also open an upload stream and write contents yourself.

.. code-block:: php

    <?php

    $repository = $documentManager->getRepository(Documents\Image::class);
    $file = $repository->uploadFromFile('/tmp/path/to/image', 'image.jpg');

When using the default GridFS repository implementation, the ``uploadFromFile``
and ``uploadFromStream`` methods return a proxy object of the file you just
uploaded.

If you want to pass options, such as a metadata object to the uploaded file, you
can pass an ``UploadOptions`` object as the last argument to the
``uploadFromFile``, ``uploadFromStream``, or ``openUploadStream`` method call:

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Repository\UploadOptions;

    $uploadOptions = new UploadOptions();
    $uploadOptions->metadata = new Documents\ImageMetadata('image/jpeg');
    $uploadOptions->chunkSizeBytes = 1024 * 1024;

    $repository = $documentManager->getRepository(Documents\Image::class);
    $file = $repository->uploadFromFile('/tmp/path/to/image', 'image.jpg', $uploadOptions);

Reading files from GridFS buckets
---------------------------------

When reading GridFS files, they behave like all other documents. You can query
for them using the ``find*`` methods in the repository, create query or
aggregation pipeline builders, and also use them as ``targetDocument`` in
references. You can access all properties of the file including metadata, but
not file content.

The GridFS specification uses streams to deal with file contents. To avoid
having this resource overhead every time you fetch a file from the database,
file contents are only provided through the ``downloadToStream`` repository
method. Accessors to provide a stream in the document may be implemented in
future versions.

The following code sample puts the file contents into a different file after
uploading:

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Repository\UploadOptions;

    $uploadOptions = new UploadOptions();
    $uploadOptions->metadata = new Documents\ImageMetadata('image/jpeg');

    $repository = $documentManager->getRepository(Documents\Image::class);
    $file = $repository->uploadFromFile('/tmp/path/to/image', 'image.jpg', $uploadOptions);

    $stream = fopen('tmp/path/to/copy', 'w+');
    try {
        $repository->downloadToStream($file->getId(), $stream);
    } finally {
        fclose($stream);
    }

The ``downloadToStream`` method takes the identifier of a file as first argument
and a writable stream as the second arguments. If you need to manipulate the
file contents before writing it to disk or sending it to the client, consider
using a memory stream using the ``php://memory`` stream wrapper.

Alternatively, you can also use the ``openDownloadStream`` method which returns
a stream from where you can read file contents:

.. code-block:: php

    <?php

    use Doctrine\ODM\MongoDB\Repository\UploadOptions;

    $uploadOptions = new UploadOptions();
    $uploadOptions->metadata = new Documents\ImageMetadata('image/jpeg');

    $repository = $documentManager->getRepository(Documents\Image::class);
    $file = $repository->uploadFromFile('/tmp/path/to/image', 'image.jpg', $uploadOptions);

    $stream = $repository->openDownloadStream($file->getId());
    try {
        $contents = stream_get_contents($stream);
    } finally {
        fclose($stream);
    }

