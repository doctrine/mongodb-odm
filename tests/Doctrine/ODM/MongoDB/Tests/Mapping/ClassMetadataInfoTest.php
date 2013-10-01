<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;

class ClassMetadataInfoTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testDefaultDiscriminatorField()
    {
        $cm = new ClassMetadataInfo('stdClass');

        $cm->mapField(array(
            'fieldName' => 'assoc',
            'reference' => true,
            'type' => 'one',
        ));

        $cm->mapField(array(
            'fieldName' => 'assocWithTargetDocument',
            'reference' => true,
            'type' => 'one',
            'targetDocument' => 'stdClass',
        ));

        $cm->mapField(array(
            'fieldName' => 'assocWithDiscriminatorField',
            'reference' => true,
            'type' => 'one',
            'discriminatorField' => 'type',
        ));

        $mapping = $cm->getFieldMapping('assoc');

        $this->assertEquals(
            ClassMetadataInfo::DEFAULT_DISCRIMINATOR_FIELD, $mapping['discriminatorField'],
            'Default discriminator field is set for associations without targetDocument and discriminatorField options'
        );

        $mapping = $cm->getFieldMapping('assocWithTargetDocument');

        $this->assertArrayNotHasKey(
            'discriminatorField', $mapping,
            'Default discriminator field is not set for associations with targetDocument option'
        );

        $mapping = $cm->getFieldMapping('assocWithDiscriminatorField');

        $this->assertEquals(
            'type', $mapping['discriminatorField'],
            'Default discriminator field is not set for associations with discriminatorField option'
        );
    }

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
