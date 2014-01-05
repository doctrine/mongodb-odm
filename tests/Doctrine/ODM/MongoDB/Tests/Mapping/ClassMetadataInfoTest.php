<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Documents\Album;

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
        $document = new Album('ten');
        $metadata = $this->dm->getClassMetadata('Documents\Album');

        $this->assertEquals($document->getName(), $metadata->getFieldValue($document, 'name'));
    }

    public function testGetFieldValueInitializesProxy()
    {
        $document = new Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $proxy = $this->dm->getReference('Documents\Album', $document->getId());
        $metadata = $this->dm->getClassMetadata('Documents\Album');

        $this->assertEquals($document->getName(), $metadata->getFieldValue($proxy, 'name'));
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $proxy);
        $this->assertTrue($proxy->__isInitialized());
    }

    public function testGetFieldValueOfIdentifierDoesNotInitializeProxy()
    {
        $document = new Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $proxy = $this->dm->getReference('Documents\Album', $document->getId());
        $metadata = $this->dm->getClassMetadata('Documents\Album');

        $this->assertEquals($document->getId(), $metadata->getFieldValue($proxy, 'id'));
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $proxy);
        $this->assertFalse($proxy->__isInitialized());
    }

    public function testSetFieldValue()
    {
        $document = new Album('ten');
        $metadata = $this->dm->getClassMetadata('Documents\Album');

        $metadata->setFieldValue($document, 'name', 'nevermind');

        $this->assertEquals('nevermind', $document->getName());
    }

    public function testSetFieldValueWithProxy()
    {
        $document = new Album('ten');
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $proxy = $this->dm->getReference('Documents\Album', $document->getId());
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $proxy);

        $metadata = $this->dm->getClassMetadata('Documents\Album');
        $metadata->setFieldValue($proxy, 'name', 'nevermind');

        $this->dm->flush();
        $this->dm->clear();

        $proxy = $this->dm->getReference('Documents\Album', $document->getId());
        $this->assertInstanceOf('Doctrine\ODM\MongoDB\Proxy\Proxy', $proxy);

        $this->assertEquals('nevermind', $proxy->getName());
    }

    public function testSetCustomRepositoryClass()
    {
        $cm = new ClassMetadataInfo('Doctrine\ODM\MongoDB\Tests\Mapping\ClassMetadataInfoTest');
        $cm->namespace = 'Doctrine\ODM\MongoDB\Tests\Mapping';

        $cm->setCustomRepositoryClass('TestCustomRepositoryClass');

        $this->assertEquals('Doctrine\ODM\MongoDB\Tests\Mapping\TestCustomRepositoryClass', $cm->customRepositoryClassName);

        $cm->setCustomRepositoryClass('Doctrine\ODM\MongoDB\Tests\Mapping\TestCustomRepositoryClass');

        $this->assertEquals('Doctrine\ODM\MongoDB\Tests\Mapping\TestCustomRepositoryClass', $cm->customRepositoryClassName);
    }
}

class TestCustomRepositoryClass extends DocumentRepository
{
}
