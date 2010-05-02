<?php

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Cache\ApcCache,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver,
    Documents\Account,
    Documents\Address,
    Documents\Group,
    Documents\Phonenumber,
    Documents\Profile,
    Documents\File,
    Documents\User;

abstract class BaseTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $config = new Configuration();

        $reader = new AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\Driver\\');
        $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));

        $this->dm = DocumentManager::create(new Mongo(), $config);
    }

    public function tearDown()
    {
        $documents = array(
            'Documents\User',
            'Documents\Account',
            'Documents\Profile',
            'Documents\Address',
            'Documents\Group',
            'Documents\Phonenumber',
            'Documents\File'
        );
        foreach ($documents as $document) {
            $this->dm->getDocumentCollection($document)->drop();
        }
    }

    protected function _createTestUser()
    {
        $user = new User();
        $user->username = 'jwage';
        $user->password = 'changeme';
        $user->profile = new Profile();
        $user->profile->firstName = 'Jonathan';
        $user->profile->lastName = 'Wage';
        
        $this->dm->persist($user);
        $this->dm->flush();

        return $user;
    }
}