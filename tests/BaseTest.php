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

        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setDefaultDB('doctrine_odm_tests');

        /*
        $config->setLoggerCallable(function(array $log) {
            print_r($log);
        });
        $config->setMetadataCacheImpl(new ApcCache());
        */

        $reader = new AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
        $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));

        $this->dm = DocumentManager::create(new Mongo(), $config);
    }

    public function tearDown()
    {
        $documents = array(
            'Documents\User',
            'Documents\SpecialUser',
            'Documents\Account',
            'Documents\Profile',
            'Documents\Group',
            'Documents\File',
            'Documents\Article'
        );
        foreach ($documents as $document) {
            $this->dm->getDocumentCollection($document)->drop();
        }
    }
}