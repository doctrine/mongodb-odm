<?php

require_once 'TestInit.php';

use Doctrine\Common\ClassLoader,
    Doctrine\Common\Cache\ApcCache,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ODM\MongoDB\DocumentManager,
    Doctrine\ODM\MongoDB\Configuration,
    Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mongo,
    Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

class EnvironmentTest extends PHPUnit_Framework_TestCase
{
    protected $dbNames = array
    (
        'specified' => 'test_testing',
        'unspecified' => 'test_testing_default',
    );

    protected $defaultDB = 'testing_default';

    public function setUp()
    {
        $config = new Configuration();

        $config->setProxyDir(__DIR__ . '/Proxies');
        $config->setProxyNamespace('Proxies');
        $config->setEnvironment('test');
        $config->setDefaultDB($this->defaultDB);

        $reader = new AnnotationReader();
        $reader->setDefaultAnnotationNamespace('Doctrine\ODM\MongoDB\Mapping\\');
        $config->setMetadataDriverImpl(new AnnotationDriver($reader, __DIR__ . '/Documents'));

        $this->dm = DocumentManager::create(new Mongo(), $config);
    }

    public function testDefaultDb()
    {
        $dbname = $this->dm->getConfiguration()->getDefaultDB();
        $this->assertEquals($this->defaultDB, $dbname);
    }

    public function testSetEnvironmentForDocumentUnspecifiedDb()
    {
        $dbname = $this->dm->getDocumentDB('TestEmptyDatabase')->getName();
        $this->assertEquals($this->dbNames['unspecified'], $dbname);
    }

    public function testSetEnvironment()
    {
        $dbname  = $this->dm->getDocumentDB('TestDocument')->getName();

        $this->assertEquals($this->dbNames['specified'], $dbname);
    }

    public function testPersist()
    {
        $doc = new TestDocument();
        $doc->setName('test');
        $this->dm->persist($doc);

        $doc2 = new TestEmptyDatabase();
        $doc2->setName('document with unspecified database name');
        $this->dm->persist($doc2);

        $this->dm->flush();

        $mongo = $this->dm->getMongo()->getMongo();
        $coll = $mongo->selectDB($this->dbNames['specified'])
            ->selectCollection('documents');
        $coll2 = $mongo->selectDB($this->dbNames['unspecified'])
            ->selectCollection('documents');

        $vDoc = $coll->findOne(array(
            '_id' => new \MongoId($doc->getId()),
        ));

        $vDoc2 = $coll2->findOne(array(
            '_id' => new \MongoId($doc2->getId())
        ));

        $this->assertEquals($doc->getId(), (string)$vDoc['_id']);
        $this->assertEquals($doc2->getId(), (string)$vDoc2['_id']);
    }

    public function testFind()
    {
        $mongo = $this->dm->getMongo()->getMongo();
        $collection = $mongo->selectDB($this->dbNames['specified'])
            ->selectCollection('documents');
        $testDoc = array('name' => 'test doc');
        $collection->insert($testDoc);

        $this->assertTrue($testDoc['_id'] instanceof \MongoId);

        $doc = $this->dm->find('TestDocument', (string)$testDoc['_id']);

        $this->assertEquals($doc->getId(), (string)$testDoc['_id']);
    }

    public function testFindForDocWithUnspecifiedDb()
    {
        $mongo = $this->dm->getMongo()->getMongo();
        $collection = $mongo->selectDB($this->dbNames['unspecified'])
            ->selectCollection('documents');
        $testDoc = array('name' => 'test doc with unspecified db name.');
        $collection->insert($testDoc);

        $this->assertTrue($testDoc['_id'] instanceof \MongoId);

        $doc = $this->dm->find('TestEmptyDatabase', (string)$testDoc['_id']);

        $this->assertEquals($doc->getId(), (string)$testDoc['_id']);
    }

    public function testCustomQuery()
    {
        $mongo = $this->dm->getMongo()->getMongo();
        $coll = $mongo->selectDB($this->dbNames['specified'])
            ->selectCollection('documents');
        $doc = array('name' => 'test doc');
        $coll->insert($doc);

        $docName = $this->dm->createQuery('TestDocument')
            ->where('name', 'test doc')
            ->getSingleResult();

        $this->assertEquals((string)$doc['_id'], $docName->getId());
    }

    public function tearDown()
    {
        $documents = array(
            'TestDocument',
            'TestEmptyDatabase',
        );
        foreach ($documents as $document) {
            $this->dm->getDocumentCollection($document)->drop();
        }
    }
}

/** @Document(db="testing", collection="documents") */
class TestDocument
{
    /** @Id */
    private $id;

    /** @Field(type="string") */
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }
}

/** @Document(collection="documents") */
class TestEmptyDatabase
{
    /** @Id */
    private $id;

    /** @Field(type="string") */
    private $name;

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name;
    }

    public function getName()
    {
        return $this->name;
    }
}
