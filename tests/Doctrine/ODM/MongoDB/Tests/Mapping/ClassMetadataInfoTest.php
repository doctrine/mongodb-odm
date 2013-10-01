<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

class ClassMetadataInfoTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testGetFieldValue()
    {
        //load some metadata
        $metadata = $this->dm->getClassMetadata('Documents\Album');

        //create a document
        $document = new \Documents\Album('ten');

        $this->assertEquals('ten', $metadata->getFieldValue($document, 'name'));
    }

    public function testGetFieldValueWithProxy()
    {
        //create and persist a document
        $document = new \Documents\Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        //get the proxy
        $proxy = $this->dm->getReference('Documents\Album', $document->getId());

        //get metadata
        $metadata = $this->dm->getClassMetadata('Documents\Album');

        $this->assertEquals('ten', $metadata->getFieldValue($proxy, 'name'));
    }

    public function testGetIdentifierFieldValueWithProxy()
    {
        //create and persist a document
        $document = new \Documents\Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        //get the proxy
        $proxy = $this->dm->getReference('Documents\Album', $document->getId());

        //get metadata
        $metadata = $this->dm->getClassMetadata('Documents\Album');

        $metadata->getFieldValue($proxy, 'id');
        
        $this->assertFalse($proxy->__isInitialized());
    }

    public function testSetFieldValue()
    {
        //load some metadata
        $metadata = $this->dm->getClassMetadata('Documents\Album');

        //create a document
        $document = new \Documents\Album('ten');

        //set a field
        $metadata->setFieldValue($document, 'name', 'nevermind');

        $this->assertEquals('nevermind', $document->getName());
    }

    public function testSetFieldValueWithProxy()
    {
        //create and persist a document
        $document = new \Documents\Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        //get the proxy
        $proxy = $this->dm->getReference('Documents\Album', $document->getId());

        //get metadata
        $metadata = $this->dm->getClassMetadata('Documents\Album');

        //set a field
        $metadata->setFieldValue($proxy, 'name', 'nevermind');

        //flush changes
        $this->dm->flush();
        $this->dm->clear();

        //check that changes did get flushed
        $proxy = $this->dm->getReference('Documents\Album', $document->getId());

        $this->assertEquals('nevermind', $proxy->getName());
    }
}