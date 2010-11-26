<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\MongoGridFSFile;

class MongoGridFSFileTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSetAndGetMongoGridFSFile()
    {
        $path = __DIR__.'/MongoGridFSFileTest.php';
        $file = $this->getTestMongoGridFSFile($path);
        $mockPHPMongoGridFSFile = $this->getMockPHPMongoGridFSFile();
        $file->setMongoGridFSFile($mockPHPMongoGridFSFile);
        $this->assertEquals($mockPHPMongoGridFSFile, $file->getMongoGridFSFile());
    }

    public function testIsDirty()
    {
        $file = $this->getTestMongoGridFSFile();
        $this->assertFalse($file->isDirty());
        $file->isDirty(true);
        $this->assertTrue($file->isDirty());
        $file->isDirty(false);
        $this->assertFalse($file->isDirty());
    }

    public function testSetAndGetFilename()
    {
        $path = __DIR__.'/MongoGridFSFileTest.php';
        $file = $this->getTestMongoGridFSFile();
        $this->assertFalse($file->isDirty());
        $file->setFilename($path);
        $this->assertTrue($file->isDirty());
        $this->assertFalse($file->hasUnpersistedBytes());
        $this->assertTrue($file->hasUnpersistedFile());
        $this->assertEquals($path, $file->getFilename());
    }

    public function testSetBytes()
    {
        $file = $this->getTestMongoGridFSFile();
        $file->setBytes('bytes');
        $this->assertTrue($file->isDirty());
        $this->assertTrue($file->hasUnpersistedBytes());
        $this->assertFalse($file->hasUnpersistedFile());
        $this->assertEquals('bytes', $file->getBytes());
    }

    public function testWriteWithSetBytes()
    {
        $file = $this->getTestMongoGridFSFile();
        $file->setBytes('bytes');
        $path = '/tmp/doctrine'.__CLASS__.'_write_test';
        $file->write($path);
        $this->assertTrue(file_exists($path));
        $this->assertEquals('bytes', file_get_contents($path));
        unlink($path);
    }

    public function testWriteWithSetFilename()
    {
        $origPath = __DIR__.'/MongoGridFSFileTest.php';
        $file = $this->getTestMongoGridFSFile();
        $file->setFilename($origPath);
        $path = '/tmp/doctrine'.__CLASS__.'_write_test';
        $file->write($path);
        $this->assertTrue(file_exists($path));
        $this->assertEquals(file_get_contents($origPath), file_get_contents($path));
        unlink($path);
    }

    public function testGetSizeWithSetBytes()
    {
        $file = $this->getTestMongoGridFSFile();
        $file->setBytes('bytes');
        $this->assertEquals(5, $file->getSize());
    }

    public function testGetSizeWithSetFilename()
    {
        $file = $this->getTestMongoGridFSFile();
        $file->setFilename(__DIR__.'/Functional/file.txt');
        $this->assertEquals(22, $file->getSize());
    }

    public function testFunctional()
    {
        $path = __DIR__.'/Functional/file.txt';
        $db = $this->dm->getMongo()->selectDB('test_files');
        $gridFS = $db->getGridFS();
        $id = $gridFS->storeFile($path);
        $file = $gridFS->findOne(array('_id' => $id));
        $file = new MongoGridFSFile($file);
        $this->assertFalse($file->isDirty());
        $this->assertEquals($path, $file->getFilename());
        $this->assertEquals(file_get_contents($path), $file->getBytes());
        $this->assertEquals(22, $file->getSize());

        $tmpPath = '/tmp/doctrine'.__CLASS__.'_write_test';
        $file->write($tmpPath);
        $this->assertTrue(file_exists($path));
        $this->assertEquals(file_get_contents($path), file_get_contents($tmpPath));
        unlink($tmpPath);
    }

    private function getMockPHPMongoGridFSFile()
    {
        return $this->getMock('MongoGridFSFile', array(), array(), '', false, false);
    }

    private function getTestMongoGridFSFile($file = null)
    {
        return new MongoGridFSFile($file);
    }
}