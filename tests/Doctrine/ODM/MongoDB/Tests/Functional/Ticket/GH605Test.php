<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Configuration;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;
use Doctrine\MongoDB\Connection;

/**
 * @package Doctrine\ODM\MongoDB\Tests\Functional\Ticket
 *
 * @group GH605
 */
class GH605Test extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private $dm2;

    public function setUp()
    {
        parent::setUp();

        $this->getConnection();
    }

    public function getConnection()
    {
        $config = new Configuration();
        $config->setProxyDir(__DIR__ . '/../../../../Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setHydratorDir(__DIR__ . '/../../../../Hydrators');
        $config->setHydratorNamespace('Hydrators');
        $config->setDefaultDB('doctrine_odm_tests_2');
        $config->addFilter('testFilter', 'Doctrine\ODM\MongoDB\Tests\Query\Filter\Filter');

        $reader = new AnnotationReader();
        $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/../../../../Documents'));

        $conn = new Connection(null, array(), $config);
        $this->dm2 = DocumentManager::create($conn, $config);
        $this->uow = $this->dm->getUnitOfWork();

    }

    public function testCopyDocument()
    {
        $embeddedDocuments = array(new GH605TestEmbeddedDocument('foo'));

        $testDoc = new GH605TestDocument();
        $testDoc->setEmbeddedDocuments($embeddedDocuments);
        $this->dm->persist($testDoc);
        $this->dm->flush();
        $this->dm->clear();

        $testDoc = $this->dm->find(__NAMESPACE__.'\GH605TestDocument', $testDoc->id);
        $this->assertEquals($embeddedDocuments, $testDoc->embeddedDocuments->toArray(), 'references has been copied correctly in db2');

        $this->dm2->persist($testDoc);
        $this->dm2->flush();
        $this->dm2->clear();

        $testDocLoad = $this->dm2->find(__NAMESPACE__.'\GH605TestDocument', $testDoc->id);
        $this->assertNotNull($testDocLoad);
        $this->assertEquals($embeddedDocuments, $testDocLoad->embeddedDocuments->toArray(), 'references has been copied correctly in db2');

        $this->dm->remove($testDoc);
        $this->dm->flush();
        $this->dm->clear();

        $testDocLoad = $this->dm->find(__NAMESPACE__.'\GH605TestDocument', $testDoc->id);
        $this->assertNull($testDocLoad, 'doc was removed correctly');
    }
}

/** @ODM\Document */
class GH605TestDocument
{
    /** @ODM\Id */
    public $id;

    // Note: Test case fails with default "pushAll" strategy, but "set" works
    /** @ODM\EmbedMany(targetDocument="GH605TestEmbeddedDocument") */
    public $embeddedDocuments;

    public function __construct() {
        $this->embeddedDocuments = new ArrayCollection();
    }

    /**
     * Sets children
     *
     * If $images is not an array or Traversable object, this method will simply
     * clear the images collection property.  If any elements in the parameter
     * are not an Image object, this method will attempt to convert them to one
     * by mapping array indexes (size URL's are required, cropMetadata is not).
     * Any invalid elements will be ignored.
     *
     * @param array|Traversable $children
     */
    public function setEmbeddedDocuments($embeddedDocuments) {
        $this->embeddedDocuments->clear();

        if (! (is_array($embeddedDocuments) || $embeddedDocuments instanceof \Traversable)) {
            return;
        }

        foreach ($embeddedDocuments as $embeddedDocument) {
            $this->embeddedDocuments->add($embeddedDocument);
        }
    }
}

/** @ODM\EmbeddedDocument */
class GH605TestEmbeddedDocument
{
    /** @ODM\String */
    public $name;

    public function __construct($name) {
        $this->name = $name;
    }
}