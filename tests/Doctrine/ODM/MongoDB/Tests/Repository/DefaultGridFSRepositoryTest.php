<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Repos;

use Doctrine\ODM\MongoDB\Repository\GridFSRepository;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\File;
use Documents\FileMetadata;
use Documents\User;
use function fclose;
use function filesize;
use function fopen;
use function fseek;
use function fstat;
use function fwrite;
use function tmpfile;

class DefaultGridFSRepositoryTest extends BaseTest
{
    public function testOpenUploadStreamReturnsWritableResource(): void
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
        self::assertInternalType('int', $file->getChunkSize());
        self::assertEquals(new \DateTime(), $file->getUploadDate(), '', 1);
        self::assertNull($file->getMetadata());
    }

    public function testUploadFromStreamStoresFile(): void
    {
        $fileResource = fopen(__FILE__, 'r');

        try {
            /** @var File $file */
            $file = $this->getRepository()->uploadFromStream('somefile.txt', $fileResource, new FileMetadata());
        } finally {
            fclose($fileResource);
        }

        self::assertInstanceOf(File::class, $file);

        $expectedSize = filesize(__FILE__);

        // Check if the file is actually there
        self::assertInstanceOf(File::class, $this->getRepository()->findOneBy(['filename' => 'somefile.txt']));

        self::assertSame('somefile.txt', $file->getFilename());
        self::assertSame($expectedSize, $file->getLength());
        self::assertInternalType('int', $file->getChunkSize());
        self::assertEquals(new \DateTime(), $file->getUploadDate(), '', 1);
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
        /** @var File $file */
        $file = $this->getRepository()->uploadFromFile(__FILE__);

        $expectedSize = filesize(__FILE__);

        self::assertSame('DefaultGridFSRepositoryTest.php', $file->getFilename());
        self::assertSame($expectedSize, $file->getLength());

        // Check if the file is actually there
        self::assertInstanceOf(File::class, $this->getRepository()->findOneBy(['filename' => $file->getFilename()]));
    }

    public function testUploadFromFileUsesProvidedFilename(): void
    {
        /** @var File $file */
        $file = $this->getRepository()->uploadFromFile(__FILE__, 'test.php');
        self::assertSame('test.php', $file->getFilename());
    }

    public function testReadingFileAllowsUpdatingMetadata(): void
    {
        $file = $this->uploadFile(__FILE__, new FileMetadata());
        $file->getMetadata()->setOwner(new User());

        $this->dm->persist($file);
        $this->dm->flush();

        $this->dm->clear();

        /** @var File $file */
        $file = $this->getRepository()->find($file->getId());
        self::assertInstanceOf(File::class, $file);
        self::assertInstanceOf(User::class, $file->getMetadata()->getOwner());
    }

    private function getRepository(): GridFSRepository
    {
        return $this->dm->getRepository(File::class);
    }

    private function uploadFile($filename, $metadata = null): File
    {
        $fileResource = fopen($filename, 'r');

        try {
            /** @var File $file */
            $file = $this->getRepository()->uploadFromStream('somefile.txt', $fileResource, $metadata);
        } finally {
            fclose($fileResource);
        }

        self::assertInstanceOf(File::class, $file);

        return $file;
    }
}
