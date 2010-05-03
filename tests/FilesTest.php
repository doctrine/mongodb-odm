<?php

require_once 'TestInit.php';

use Documents\File,
    Documents\Profile;

class FilesTest extends BaseTest
{
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

        $this->assertEquals('These are the bytes...', $image->getFile()->getBytes());

        $image->setName('testing');
        $this->dm->flush();
        $this->dm->clear();

        $image = $this->dm->findByID('Documents\File', $image->getId());

        $this->assertEquals('testing', $image->getName());
        $this->assertEquals('These are the bytes...', $image->getFile()->getBytes());
    }
}