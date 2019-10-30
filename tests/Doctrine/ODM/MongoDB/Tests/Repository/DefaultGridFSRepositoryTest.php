<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Repos;

use DateTime;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Repository\GridFSRepository;
use Doctrine\ODM\MongoDB\Repository\UploadOptions;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\File;
use Documents\FileMetadata;
use Documents\FileWithoutChunkSize;
use Documents\FileWithoutMetadata;
use Documents\User;
use function fclose;
use function filesize;
use function fopen;
use function fseek;
use function fstat;
use function fwrite;
use function sprintf;
use function tmpfile;

class DefaultGridFSRepositoryTest extends BaseTest
{
    public function testOpenUploadStreamReturnsWritableResource() : void
    {
        $uploadStream = $this->getRepository()->openUploadStream('somefile.txt');
        self::assertInternalType('resource', $uploadStream);

        fwrite($uploadStream, 'contents');
        fclose($uploadStream);

        /** @var File $file */
        $file = $this->getRepository()->findOneBy(['filename' => 'somefile.txt']);
        self::assertInstanceOf(File::class, $file);

        self::assertSame('somefile.txt', $file->getFilename());
        self::assertSame(8, $file->getLength());
        self::assertSame(12345, $file->getChunkSize());
        self::assertEquals(new DateTime(), $file->getUploadDate(), '', 1);
        self::assertNull($file->getMetadata());
    }

    public function testOpenUploadStreamUsesChunkSizeFromOptions() : void
    {
        $uploadOptions                 = new UploadOptions();
        $uploadOptions->chunkSizeBytes = 1234;

        $uploadStream = $this->getRepository()->openUploadStream('somefile.txt', $uploadOptions);
        self::assertInternalType('resource', $uploadStream);

        fwrite($uploadStream, 'contents');
        fclose($uploadStream);

        /** @var File $file */
        $file = $this->getRepository()->findOneBy(['filename' => 'somefile.txt']);
        self::assertInstanceOf(File::class, $file);

        self::assertSame('somefile.txt', $file->getFilename());
        self::assertSame(8, $file->getLength());
        self::assertSame(1234, $file->getChunkSize());
        self::assertEquals(new DateTime(), $file->getUploadDate(), '', 1);
        self::assertNull($file->getMetadata());
    }

    public function testUploadFromStreamStoresFile() : void
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
            /** @var File $file */
            $file = $this->getRepository()->uploadFromStream('somefile.txt', $fileResource, $uploadOptions);
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
        self::assertEquals(new DateTime(), $file->getUploadDate(), '', 1);
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

    public function testOpenDownloadStreamAllowsReadingFile() : void
    {
        /** @var File $file */
        $file = $this->getRepository()->uploadFromFile(__FILE__);
        self::assertInstanceOf(File::class, $file);

        $expectedSize = filesize(__FILE__);

        $stream = $this->getRepository()->openDownloadStream($file->getId());

        fseek($stream, 0);
        $stat = fstat($stream);
        self::assertSame($expectedSize, $stat['size']);
        fclose($stream);
    }

    public function testUploadFromStreamPassesChunkSize() : void
    {
        $uploadOptions                 = new UploadOptions();
        $uploadOptions->metadata       = new FileMetadata();
        $uploadOptions->chunkSizeBytes = 1234;

        $fileResource = fopen(__FILE__, 'r');

        try {
            /** @var File $file */
            $file = $this->getRepository()->uploadFromStream('somefile.txt', $fileResource, $uploadOptions);
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
        self::assertEquals(new DateTime(), $file->getUploadDate(), '', 1);
        self::assertInstanceOf(FileMetadata::class, $file->getMetadata());

        $stream = tmpfile();
        $this->getRepository()->downloadToStream($file->getId(), $stream);

        fseek($stream, 0);
        $stat = fstat($stream);
        self::assertSame($expectedSize, $stat['size']);
        fclose($stream);
    }

    public function testUploadFromFileWithoutFilenamePicksAFilename() : void
    {
        /** @var File $file */
        $file = $this->getRepository()->uploadFromFile(__FILE__);

        $expectedSize = filesize(__FILE__);

        self::assertSame('DefaultGridFSRepositoryTest.php', $file->getFilename());
        self::assertSame($expectedSize, $file->getLength());
        self::assertSame(12345, $file->getChunkSize());

        // Check if the file is actually there
        self::assertInstanceOf(File::class, $this->getRepository()->findOneBy(['filename' => $file->getFilename()]));
    }

    public function testUploadFromFileUsesProvidedFilename() : void
    {
        $uploadOptions                 = new UploadOptions();
        $uploadOptions->chunkSizeBytes = 1234;

        /** @var File $file */
        $file = $this->getRepository()->uploadFromFile(__FILE__, 'test.php', $uploadOptions);
        self::assertSame('test.php', $file->getFilename());
        self::assertSame(1234, $file->getChunkSize());
    }

    public function testReadingFileAllowsUpdatingMetadata() : void
    {
        $uploadOptions           = new UploadOptions();
        $uploadOptions->metadata = new FileMetadata();

        $file = $this->uploadFile(__FILE__, $uploadOptions);
        $file->getMetadata()->setOwner(new User());

        $this->dm->persist($file);
        $this->dm->flush();

        $this->dm->clear();

        /** @var File $file */
        $file = $this->getRepository()->find($file->getId());
        self::assertInstanceOf(File::class, $file);
        self::assertInstanceOf(User::class, $file->getMetadata()->getOwner());
    }

    public function testDeletingFileAlsoDropsChunks() : void
    {
        $file = $this->uploadFile(__FILE__);

        $this->dm->remove($file);
        $this->dm->flush();

        $bucket = $this->dm->getDocumentBucket(File::class);

        self::assertSame(0, $bucket->getFilesCollection()->count());
        self::assertSame(0, $bucket->getChunksCollection()->count());
    }

    public function testUploadMetadataForFileWithoutMetadata()
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

    public function testUploadFileWithoutChunkSize()
    {
        /** @var FileWithoutChunkSize $file */
        $file = $this->getRepository(FileWithoutChunkSize::class)->uploadFromFile(__FILE__);

        $expectedSize = filesize(__FILE__);

        self::assertSame('DefaultGridFSRepositoryTest.php', $file->getFilename());
        self::assertSame($expectedSize, $file->getLength());
        self::assertSame(261120, $file->getChunkSize());
    }

    private function getRepository($className = File::class) : GridFSRepository
    {
        return $this->dm->getRepository($className);
    }

    private function uploadFile($filename, ?UploadOptions $uploadOptions = null) : File
    {
        $fileResource = fopen($filename, 'r');

        try {
            /** @var File $file */
            $file = $this->getRepository()->uploadFromStream('somefile.txt', $fileResource, $uploadOptions);
        } finally {
            fclose($fileResource);
        }

        self::assertInstanceOf(File::class, $file);

        return $file;
    }
}
