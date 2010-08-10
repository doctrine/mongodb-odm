<?php

namespace Doctrine\ODM\MongoDB\Tests;

require_once __DIR__ . '/../../../../TestInit.php';

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class DocumentManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testCustomRepository()
    {
        $dm = $this->getDocumentManager();
        $this->assertInstanceOf('Documents\CustomRepository\Repository', $dm->getRepository('Documents\CustomRepository\Document'));
    }

    protected function getDocumentManager()
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../../../Proxies');
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
        return DocumentManager::create($this->getMongo(), $config);
    }

    protected function getMongo()
    {
        return $this->getMock('Doctrine\ODM\MongoDB\Mongo');
    }
}