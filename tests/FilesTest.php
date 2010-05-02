<?php

require_once 'TestInit.php';

use Documents\File,
    Documents\Profile;

class FilesTest extends BaseTest
{
    public function testFiles()
    {
        $image = new File();
        $image->name = 'Test';
        $image->file = __DIR__ . '/file.txt';

        $profile = new Profile();
        $profile->firstName = 'Jon';
        $profile->lastName = 'Wage';
        $profile->image = $image;

        $this->dm->persist($profile);
        $this->dm->flush();

        $this->assertEquals('These are the bytes...', $image->file->getBytes());

        $image->name = 'testing';
        $this->dm->flush();
        $this->dm->clear();

        $image = $this->dm->findByID('Documents\File', $image->id);

        $this->assertEquals('testing', $image->name);
        $this->assertEquals('These are the bytes...', $image->file->getBytes());
    }
}