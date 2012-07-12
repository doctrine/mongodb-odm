<?php

namespace Doctrine\ODM\MongoDB\Tests;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

/**
 * @author Bulat Shakirzyanov <mallluhuct@gmail.com>
 */
class DocumentManagerTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testCustomRepository()
    {
        $dm = $this->getDocumentManager();
        $this->assertInstanceOf('Documents\CustomRepository\Repository', $dm->getRepository('Documents\CustomRepository\Document'));
    }

    public function testGetConnection()
    {
        $this->assertInstanceOf('\Doctrine\MongoDB\Connection', $this->dm->getConnection());
    }

    public function testGetMetadataFactory()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\Mapping\ClassMetadataFactory', $this->dm->getMetadataFactory());
    }

    public function testGetConfiguration()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\Configuration', $this->dm->getConfiguration());
    }

    public function testGetUnitOfWork()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\UnitOfWork', $this->dm->getUnitOfWork());
    }

    public function testGetProxyFactory()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\Proxy\ProxyFactory', $this->dm->getProxyFactory());
    }

    public function testGetEventManager()
    {
        $this->assertInstanceOf('\Doctrine\Common\EventManager', $this->dm->getEventManager());
    }

    public function testGetSchemaManager()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\SchemaManager', $this->dm->getSchemaManager());
    }

    public function testCreateQueryBuilder()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\Query\Builder', $this->dm->createQueryBuilder());
    }

    public function testGetFilterCollection()
    {
        $this->assertInstanceOf('\Doctrine\ODM\MongoDB\Query\FilterCollection', $this->dm->getFilterCollection());
    }  
    
    public function testGetPartialReference()
    {
        $user = $this->dm->getPartialReference('Documents\CmsUser', 42);
        $this->assertTrue($this->dm->contains($user));
        $this->assertEquals(42, $user->id);
        $this->assertNull($user->getName());
    }

    static public function dataMethodsAffectedByNoObjectArguments()
    {
        return array(
            array('persist'),
            array('remove'),
            array('merge'),
            array('refresh'),
            array('detach')
        );
    }

    /**
     * @dataProvider dataMethodsAffectedByNoObjectArguments
     * @expectedException \InvalidArgumentException
     * @param string $methodName
     */
    public function testThrowsExceptionOnNonObjectValues($methodName) {
        $this->dm->$methodName(null);
    }

    static public function dataAffectedByErrorIfClosedException()
    {
        return array(
            array('flush'),
            array('persist'),
            array('remove'),
            array('merge'),
            array('refresh'),
        );
    }

    /**
     * @dataProvider dataAffectedByErrorIfClosedException
     * @param string $methodName
     */
    public function testAffectedByErrorIfClosedException($methodName)
    {
        $this->setExpectedException('Doctrine\ODM\MongoDB\MongoDBException', 'closed');

        $this->dm->close();
        if ($methodName === 'flush') {
            $this->dm->$methodName();
        } else {
            $this->dm->$methodName(new \stdClass());
        }
    }

    protected function getDocumentManager()
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/../../../../Proxies');
        $config->setProxyNamespace('Proxies');

        $config->setHydratorDir(__DIR__ . '/../../../../Hydrators');
        $config->setHydratorNamespace('Hydrators');

        $config->setDefaultDB('doctrine_odm_tests');

        /*
        $config->setLoggerCallable(function(array $log) {
            print_r($log);
        });
        $config->setMetadataCacheImpl(new ApcCache());
        */

        $reader = new AnnotationReader();
        $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));
        return DocumentManager::create($this->getConnection(), $config);
    }

    protected function getConnection()
    {
        return $this->getMock('Doctrine\MongoDB\Connection');
    }
}
