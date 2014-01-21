<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\DocumentRepository;
use Doctrine\ODM\MongoDB\Events;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Documents\Album;
use Documents\SpecialUser;
use Documents\User;

class ClassMetadataInfoTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSlaveOkayDefault()
    {
        $cm = new ClassMetadataInfo('stdClass');

        $this->assertNull($cm->slaveOkay);
    }

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

    public function testEmbeddedAssociationsAlwaysCascade()
    {
        $class = $this->dm->getClassMetadata(__NAMESPACE__ . '\EmbeddedAssociationsCascadeTest');

        $this->assertTrue($class->fieldMappings['address']['isCascadeRemove']);
        $this->assertTrue($class->fieldMappings['address']['isCascadePersist']);
        $this->assertTrue($class->fieldMappings['address']['isCascadeRefresh']);
        $this->assertTrue($class->fieldMappings['address']['isCascadeMerge']);
        $this->assertTrue($class->fieldMappings['address']['isCascadeDetach']);
        $this->assertTrue($class->fieldMappings['address']['isCascadeCallbacks']);

        $this->assertTrue($class->fieldMappings['addresses']['isCascadeRemove']);
        $this->assertTrue($class->fieldMappings['addresses']['isCascadePersist']);
        $this->assertTrue($class->fieldMappings['addresses']['isCascadeRefresh']);
        $this->assertTrue($class->fieldMappings['addresses']['isCascadeMerge']);
        $this->assertTrue($class->fieldMappings['addresses']['isCascadeDetach']);
        $this->assertTrue($class->fieldMappings['addresses']['isCascadeCallbacks']);
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage Cascade on Doctrine\ODM\MongoDB\Tests\Mapping\EmbedWithCascadeTest::address is not allowed.
     */
    public function testEmbedWithCascadeThrowsMappingException()
    {
        $class = new ClassMetadataInfo(__NAMESPACE__ . '\EmbedWithCascadeTest');
        $class->mapOneEmbedded(array(
            'fieldName' => 'address',
            'targetDocument' => 'Documents\Address',
            'cascade' => 'all',
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Expected document class "Documents\User"; found: "stdClass"
     */
    public function testInvokeLifecycleCallbacksShouldRequireInstanceOfClass()
    {
        $class = $this->dm->getClassMetadata('\Documents\User');
        $document = new \stdClass();

        $this->assertInstanceOf('\stdClass', $document);

        $class->invokeLifecycleCallbacks(Events::prePersist, $document);
    }

    public function testInvokeLifecycleCallbacksAllowsInstanceOfClass()
    {
        $class = $this->dm->getClassMetadata('\Documents\User');
        $document = new Specialuser();

        $this->assertInstanceOf('\Documents\SpecialUser', $document);

        $class->invokeLifecycleCallbacks(Events::prePersist, $document);
    }

    public function testInvokeLifecycleCallbacksShouldAllowProxyOfExactClass()
    {
        $document = new User();
        $this->dm->persist($document);
        $this->dm->flush();
        $this->dm->clear();

        $class = $this->dm->getClassMetadata('\Documents\User');
        $proxy = $this->dm->getReference('\Documents\User', $document->getId());

        $this->assertInstanceOf('\Documents\User', $proxy);

        $class->invokeLifecycleCallbacks(Events::prePersist, $proxy);
    }
}

class TestCustomRepositoryClass extends DocumentRepository
{
}

class EmbedWithCascadeTest
{
    public $address;
}

/** @ODM\Document */
class EmbeddedAssociationsCascadeTest
{
    /** @ODM\Id */
    public $id;
 
    /** @ODM\EmbedOne(targetDocument="Documents\Address") */
    public $address;

    /** @ODM\EmbedOne(targetDocument="Documents\Address") */
    public $addresses;
}
