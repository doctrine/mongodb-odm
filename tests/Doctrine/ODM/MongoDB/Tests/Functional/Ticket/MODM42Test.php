<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

require_once __DIR__ . '/../../../../../../TestInit.php';

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

        $dir = $this->dm->findOne(__NAMESPACE__.'\Directory', array());
        $this->assertNotNull($dir);
        $this->assertEquals(2, count($dir->getFiles()));
        foreach($dir->getFiles() as $file) {
            $this->assertInstanceOf('MongoGridFSFile', $file->getMongoFile());
        }
    }
}

/** @Document(collection="modm42_directories") */
class Directory
{
    /** @Id */
    protected $id;

    /** @String */
    protected $test = 'test';

    /** @ReferenceMany(targetDocument="File") */
    protected $files = array();

    public function __construct($files)
    {
        $this->files = $files;
    }

    public function getFiles()
    {
        return $this->files;
    }
}

/** @Document(collection="modm42_files") */
class File
{
    /** @Id */
    protected $id;

    /** @File */
    protected $file;

    public function __construct($path)
    {
        $this->file = $path;
    }

    public function getMongoFile() 
    {
        return $this->file;
    }
}