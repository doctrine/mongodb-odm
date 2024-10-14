<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Repository;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Repository\GridFSRepository;
use Doctrine\ODM\MongoDB\Repository\UploadOptions;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Documents\File;
use Documents\FileMetadata;
use Documents\FileWithoutChunkSize;
use Documents\FileWithoutMetadata;
use Documents\User;
use MongoDB\BSON\ObjectId;

use function assert;
use function fclose;
use function filesize;
use function fopen;
use function fseek;
use function fstat;
use function fwrite;
use function sprintf;
use function tmpfile;

class DefaultGridFSRepositoryTest extends BaseTestCase
{
    public function testOpenUploadStreamReturnsWritableResource(): void
    {
        $uploadStream = $this->getRepository()->openUploadStream('somefile.txt');
        self::assertIsResource($uploadStream);

        fwrite($uploadStream, 'contents');
        fclose($uploadStream);

        $file = $this->getRepository()->findOneBy(['filename' => 'somefile.txt']);
        assert($file instanceof File);
        self::assertInstanceOf(File::class, $file);

        self::assertSame('somefile.txt', $file->getFilename());
        self::assertSame(8, $file->getLength());
        self::assertSame(12345, $file->getChunkSize());
        self::assertEqualsWithDelta(new DateTime(), $file->getUploadDate(), 1);
        self::assertNull($file->getMetadata());
    }

    public function testOpenUploadStreamUsesIdFromOptions(): void
    {
        $uploadOptions     = new UploadOptions();
        $uploadOptions->id = new ObjectId('1234567890abcdef12345678');

        $uploadStream = $this->getRepository()->openUploadStream('somefile.txt', $uploadOptions);
        self::assertIsResource($uploadStream);

        fwrite($uploadStream, 'contents');
        fclose($uploadStream);

        $file = $this->getRepository()->findOneBy(['filename' => 'somefile.txt']);
        assert($file instanceof File);
        self::assertInstanceOf(File::class, $file);

        self::assertSame('somefile.txt', $file->getFilename());
        self::assertSame('1234567890abcdef12345678', $file->getId());
        self::assertSame(8, $file->getLength());
        self::assertSame(12345, $file->getChunkSize());
        self::assertEqualsWithDelta(new DateTime(), $file->getUploadDate(), 1);
        self::assertNull($file->getMetadata());
    }

    public function testOpenUploadStreamUsesChunkSizeFromOptions(): void
    {
        $uploadOptions                 = new UploadOptions();
        $uploadOptions->chunkSizeBytes = 1234;

        $uploadStream = $this->getRepository()->openUploadStream('somefile.txt', $uploadOptions);
        self::assertIsResource($uploadStream);

        fwrite($uploadStream, 'contents');
        fclose($uploadStream);

        $file = $this->getRepository()->findOneBy(['filename' => 'somefile.txt']);
        assert($file instanceof File);
        self::assertInstanceOf(File::class, $file);

        self::assertSame('somefile.txt', $file->getFilename());
        self::assertSame(8, $file->getLength());
        self::assertSame(1234, $file->getChunkSize());
        self::assertEqualsWithDelta(new DateTime(), $file->getUploadDate(), 1);
        self::assertNull($file->getMetadata());
    }

    public function testUploadFromStreamStoresFile(): void
    {
        $owner = new User();
        $this->dm->persist($owner);
        $this->dm->flush();

        $uploadOptions           = new UploadOptions();
        $uploadOptions->metadata = new FileMetadata();

        $uploadOptions->metadata->setOwner($owner);
        $uploadOptions->metadata->getEmbedOne()->name = 'Foo';

        $fileResource = fopen(__FILE__, 'r');

        try {
            $file = $this->getRepository()->uploadFromStream('somefile.txt', $fileResource, $uploadOptions);
            assert($file instanceof File);
        } finally {
            fclose($fileResource);
        }

        self::assertInstanceOf(File::class, $file);

        $expectedSize = filesize(__FILE__);

        // Check if the file is actually there
        self::assertInstanceOf(File::class, $this->getRepository()->findOneBy(['filename' => 'somefile.txt']));

        self::assertSame('somefile.txt', $file->getFilename());
        self::assertSame($expectedSize, $file->getLength());
        self::assertSame(12345, $file->getChunkSize());
        self::assertEqualsWithDelta(new DateTime(), $file->getUploadDate(), 1);
        self::assertInstanceOf(FileMetadata::class, $file->getMetadata());
        self::assertInstanceOf(User::class, $file->getMetadata()->getOwner());
        self::assertSame('Foo', $file->getMetadata()->getEmbedOne()->name);

        $stream = tmpfile();
        $this->getRepository()->downloadToStream($file->getId(), $stream);

        fseek($stream, 0);
        $stat = fstat($stream);
        self::assertSame($expectedSize, $stat['size']);
        fclose($stream);
    }

    public function testOpenDownloadStreamAllowsReadingFile(): void
    {
        $file = $this->getRepository()->uploadFromFile(__FILE__);
        assert($file instanceof File);
        self::assertInstanceOf(File::class, $file);

        $expectedSize = filesize(__FILE__);

        $stream = $this->getRepository()->openDownloadStream($file->getId());

        fseek($stream, 0);
        $stat = fstat($stream);
        self::assertSame($expectedSize, $stat['size']);
        fclose($stream);
    }

    public function testUploadFromStreamPassesChunkSize(): void
    {
        $uploadOptions                 = new UploadOptions();
        $uploadOptions->metadata       = new FileMetadata();
        $uploadOptions->chunkSizeBytes = 1234;

        $fileResource = fopen(__FILE__, 'r');

        try {
            $file = $this->getRepository()->uploadFromStream('somefile.txt', $fileResource, $uploadOptions);
            assert($file instanceof File);
        } finally {
            fclose($fileResource);
        }

        self::assertInstanceOf(File::class, $file);

        $expectedSize = filesize(__FILE__);

        // Check if the file is actually there
        self::assertInstanceOf(File::class, $this->getRepository()->findOneBy(['filename' => 'somefile.txt']));

        self::assertSame('somefile.txt', $file->getFilename());
        self::assertSame($expectedSize, $file->getLength());
        self::assertSame(1234, $file->getChunkSize());
        self::assertEqualsWithDelta(new DateTime(), $file->getUploadDate(), 1);
        self::assertInstanceOf(FileMetadata::class, $file->getMetadata());

        $stream = tmpfile();
        $this->getRepository()->downloadToStream($file->getId(), $stream);

        fseek($stream, 0);
        $stat = fstat($stream);
        self::assertSame($expectedSize, $stat['size']);
        fclose($stream);
    }

    public function testUploadFromFileWithoutFilenamePicksAFilename(): void
    {
        $file = $this->getRepository()->uploadFromFile(__FILE__);
        assert($file instanceof File);

        $expectedSize = filesize(__FILE__);

        self::assertSame('DefaultGridFSRepositoryTest.php', $file->getFilename());
        self::assertSame($expectedSize, $file->getLength());
        self::assertSame(12345, $file->getChunkSize());

        // Check if the file is actually there
        self::assertInstanceOf(File::class, $this->getRepository()->findOneBy(['filename' => $file->getFilename()]));
    }

    public function testUploadFromFileUsesProvidedFilename(): void
    {
        $uploadOptions                 = new UploadOptions();
        $uploadOptions->chunkSizeBytes = 1234;

        $file = $this->getRepository()->uploadFromFile(__FILE__, 'test.php', $uploadOptions);
        assert($file instanceof File);
        self::assertSame('test.php', $file->getFilename());
        self::assertSame(1234, $file->getChunkSize());
    }

    public function testReadingFileAllowsUpdatingMetadata(): void
    {
        $uploadOptions           = new UploadOptions();
        $uploadOptions->metadata = new FileMetadata();

        $file = $this->uploadFile(__FILE__, $uploadOptions);
        $file->getMetadata()->setOwner(new User());

        $this->dm->persist($file);
        $this->dm->flush();

        $this->dm->clear();

        $file = $this->getRepository()->find($file->getId());
        assert($file instanceof File);
        self::assertInstanceOf(File::class, $file);
        self::assertInstanceOf(User::class, $file->getMetadata()->getOwner());
    }

    public function testDeletingFileAlsoDropsChunks(): void
    {
        $file = $this->uploadFile(__FILE__);

        $this->dm->remove($file);
        $this->dm->flush();

        $bucket = $this->dm->getDocumentBucket(File::class);

        self::assertSame(0, $bucket->getFilesCollection()->count());
        self::assertSame(0, $bucket->getChunksCollection()->count());
    }

    public function testUploadMetadataForFileWithoutMetadata(): void
    {
        $uploadOptions           = new UploadOptions();
        $uploadOptions->metadata = new FileMetadata();

        $uploadOptions->metadata->getEmbedOne()->name = 'Foo';

        $fileResource = fopen(__FILE__, 'r');

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(sprintf("No mapping found for field by DB name 'metadata' in class '%s'.", FileWithoutMetadata::class));

        try {
            $this->getRepository(FileWithoutMetadata::class)->uploadFromStream('somefile.txt', $fileResource, $uploadOptions);
        } finally {
            fclose($fileResource);
        }
    }

    public function testUploadFileWithoutChunkSize(): void
    {
        $file = $this->getRepository(FileWithoutChunkSize::class)->uploadFromFile(__FILE__);
        assert($file instanceof FileWithoutChunkSize);

        $expectedSize = filesize(__FILE__);

        self::assertSame('DefaultGridFSRepositoryTest.php', $file->getFilename());
        self::assertSame($expectedSize, $file->getLength());
        self::assertSame(261120, $file->getChunkSize());
    }

    public function testReadingFileWithMetadata(): void
    {
        $uploadOptions                                = new UploadOptions();
        $uploadOptions->metadata                      = new FileMetadata();
        $uploadOptions->metadata->getEmbedOne()->name = 'foo';

        $file = $this->getRepository()->uploadFromFile(__FILE__, uploadOptions: $uploadOptions);
        $this->dm->detach($file);

        $retrievedFile = $this->getRepository()->find($file->getId());
        self::assertInstanceOf(File::class, $retrievedFile);
        self::assertInstanceOf(FileMetadata::class, $retrievedFile->getMetadata());
        self::assertSame('foo', $retrievedFile->getMetadata()->getEmbedOne()->name);
    }

    /**
     * @param class-string<T> $className
     *
     * @return GridFSRepository<T>
     *
     * @template T of object
     */
    private function getRepository(string $className = File::class): GridFSRepository
    {
        $repository = $this->dm->getRepository($className);

        assert($repository instanceof GridFSRepository);

        return $repository;
    }

    private function uploadFile(string $filename, ?UploadOptions $uploadOptions = null): File
    {
        $fileResource = fopen($filename, 'r');

        try {
            $file = $this->getRepository()->uploadFromStream('somefile.txt', $fileResource, $uploadOptions);
            assert($file instanceof File);
        } finally {
            fclose($fileResource);
        }

        self::assertInstanceOf(File::class, $file);

        return $file;
    }
}
