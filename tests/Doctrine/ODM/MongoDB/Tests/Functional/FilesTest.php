<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

use Documents\File,
    Documents\Profile;

class FilesTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
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

        $image = $this->dm->find('Documents\File', $image->getId());

        $this->assertEquals('testing', $image->getName());
        $this->assertEquals('These are the bytes...', $image->getFile()->getBytes());
    }
}