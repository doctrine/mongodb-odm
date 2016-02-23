<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

class MODM42Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testTest()
    {
        $file1 = new File(__DIR__ . '/MODM42/test1.txt');
        $file2 = new File(__DIR__ . '/MODM42/test2.txt');
        $dir = new Directory(array($file1, $file2));

        $this->dm->persist($file1);
        $this->dm->persist($file2);
        $this->dm->persist($dir);

        $this->assertTrue($this->dm->getUnitOfWork()->isScheduledForInsert($dir));
        $this->assertTrue($this->dm->getUnitOfWork()->isScheduledForInsert($file1));
        $this->assertTrue($this->dm->getUnitOfWork()->isScheduledForInsert($file2));

        $this->dm->flush();
        $this->dm->clear();

        $dir = $this->dm->find(__NAMESPACE__.'\Directory', $dir->getId());
        $this->assertNotNull($dir);
        $this->assertEquals(2, count($dir->getFiles()));
        foreach($dir->getFiles() as $file) {
            $this->assertInstanceOf('Doctrine\MongoDB\GridFSFile', $file->getMongoFile());
        }
    }
}

/** @ODM\Document(collection="modm42_directories") */
class Directory
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\Field(type="string") */
    protected $test = 'test';

    /** @ODM\ReferenceMany(targetDocument="File") */
    protected $files = array();

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFiles()
    {
        return $this->files;
    }
}

/** @ODM\Document(collection="modm42_files") */
class File
{
    /** @ODM\Id */
    protected $id;

    /** @ODM\File */
    protected $file;

    public function __construct($path)
    {
        $this->file = $path;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMongoFile() 
    {
        return $this->file;
    }
}
