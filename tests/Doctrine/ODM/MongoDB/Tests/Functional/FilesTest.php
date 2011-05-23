<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\File,
    Documents\Profile;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class FilesTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFlushTwice()
    {
        $image = new File();
        $image->setName('Test');
        $image->setFile(__DIR__ . '/file.txt');
        $this->dm->persist($image);
        $this->dm->flush();
        $this->dm->flush();

        $test = $this->dm->getDocumentCollection('Documents\File')->findOne();
        $this->assertFalse(isset($test->file['file']));
    }

    public function testFiles()
    {
        $image = new File();
        $image->setName('Test');
        $image->setFile(__DIR__ . '/file.txt');

        $profile = new Profile();
        $profile->setFirstName('Jon');
        $profile->setLastName('Wage');
        $profile->setImage($image);

        $this->dm->persist($profile);
        $this->dm->flush();

        $this->assertInstanceOf('Doctrine\MongoDB\GridFSFile', $image->getFile());
        $this->assertFalse($image->getFile()->isDirty());
        $this->assertEquals(__DIR__ . '/file.txt', $image->getFile()->getFilename());
        $this->assertTrue(file_exists($image->getFile()->getFilename()));
        $this->assertEquals('These are the bytes...', $image->getFile()->getBytes());

        $image->setName('testing');
        $this->dm->flush();
        $this->dm->clear();

        $image = $this->dm->find('Documents\File', $image->getId());
        $this->assertNotNull($image);
        $this->assertEquals('testing', $image->getName());
        $this->assertEquals('These are the bytes...', $image->getFile()->getBytes());
    }

    public function testCreateFileWithMongoGridFSFileObject()
    {
        $file = new \Doctrine\MongoDB\GridFSFile(__DIR__ . '/file.txt');

        $image = new File();
        $image->setName('Test');
        $image->setFile($file);

        $profile = new Profile();
        $profile->setFirstName('Jon');
        $profile->setLastName('Wage');
        $profile->setImage($image);

        $this->assertTrue($image->getFile()->isDirty());

        $this->dm->persist($profile);
        $this->dm->flush();

        $this->assertFalse($image->getFile()->isDirty());
        $this->assertSame($file, $image->getFile());

        $this->dm->clear();

        $profile = $this->dm->createQueryBuilder('Documents\Profile')
            ->getQuery()
            ->getSingleResult();
        $image = $profile->getImage();
        $this->assertInstanceOf('Doctrine\MongoDB\GridFSFile', $image->getFile());
        $this->assertEquals('These are the bytes...', $image->getFile()->getBytes());
        $image->getFile()->setFilename(__DIR__ . '/FilesTest.php');
        $this->dm->flush();
        $this->dm->clear();

        $profile = $this->dm->createQueryBuilder('Documents\Profile')
            ->getQuery()
            ->getSingleResult();
        $image = $profile->getImage();
        $this->assertEquals('Test', $image->getName());
        $this->assertEquals(__DIR__ . '/FilesTest.php', $image->getFile()->getFilename());
        $this->assertEquals(file_get_contents(__DIR__ . '/FilesTest.php'), $image->getFile()->getBytes());

        $image->getFile()->setBytes('test');
        $this->dm->flush();
        $this->dm->clear();

        $profile = $this->dm->createQueryBuilder('Documents\Profile')
            ->getQuery()
            ->getSingleResult();
        $image = $profile->getImage();
        $this->assertEquals('test', $image->getFile()->getBytes());
    }

    public function testFileWithOtherNameThanFile()
    {
        $path = __DIR__.'/FilesTest.php';

        $test = new TestFile();
        $test->name = 'Test';
        $test->theFile = new \Doctrine\MongoDB\GridFSFIle($path);

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->getRepository(__NAMESPACE__.'\TestFile')->find($test->id);
        $this->assertNotNull($test);
        $this->assertEquals(file_get_contents($path), $test->theFile->getBytes());
    }

    public function testFilesEmptyQueryReturnsNull()
    {
        $this->assertNull($this->dm->find('Documents\File', 'definitelynotanid'));
    }
}

/** @ODM\Document */
class TestFile
{
    /** @ODM\Id */
    public $id;

    /** @ODM\String */
    public $name;

    /** @ODM\File */
    public $theFile;
}