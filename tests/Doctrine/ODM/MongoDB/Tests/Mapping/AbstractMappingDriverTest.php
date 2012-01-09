<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata,
    Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver,
    Doctrine\ODM\MongoDB\Mapping\Driver\YamlDriver;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

abstract class AbstractMappingDriverTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    abstract protected function _loadDriver();

    public function testLoadMapping()
    {
        $className = __NAMESPACE__.'\User';
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata($className);
        $mappingDriver->loadMetadataForClass($className, $class);

        return $class;
    }

    /**
     * @depends testLoadMapping
     * @param ClassMetadata $class
     */
    public function testDocumentCollectionNameAndInheritance($class)
    {
        $this->assertEquals('cms_users', $class->getCollection());
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $class->inheritanceType);

        return $class;
    }

    /**
     * @depends testDocumentCollectionNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testFieldMappings($class)
    {
        $this->assertEquals(8, count($class->fieldMappings));
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['name']));
        $this->assertTrue(isset($class->fieldMappings['email']));

        return $class;
    }

    /**
     * @depends testDocumentCollectionNameAndInheritance
     * @param ClassMetadata $class
     */
    public function testStringFieldMappings($class)
    {
        $this->assertEquals('string', $class->fieldMappings['name']['type']);

        return $class;
    }

    /**
     * @depends testFieldMappings
     * @param ClassMetadata $class
     */
    public function testIdentifier($class)
    {
        $this->assertEquals('id', $class->identifier);

        return $class;
    }

    /**
     * @depends testIdentifier
     * @param ClassMetadata $class
     */
    public function testAssocations($class)
    {
        $this->assertEquals(8, count($class->fieldMappings));

        return $class;
    }

    /**
     * @depends testAssocations
     * @param ClassMetadata $class
     */
    public function testOwningOneToOneAssocation($class)
    {
        $this->assertTrue(isset($class->fieldMappings['address']));
        $this->assertTrue(is_array($class->fieldMappings['address']));
        // Check cascading
        $this->assertTrue($class->fieldMappings['address']['isCascadeRemove']);
        $this->assertFalse($class->fieldMappings['address']['isCascadePersist']);
        $this->assertFalse($class->fieldMappings['address']['isCascadeRefresh']);
        $this->assertFalse($class->fieldMappings['address']['isCascadeDetach']);
        $this->assertFalse($class->fieldMappings['address']['isCascadeMerge']);

        return $class;
    }

    /**
     * @depends testOwningOneToOneAssocation
     * @param ClassMetadata $class
     */
    public function testLifecycleCallbacks($class)
    {
        $this->assertEquals(count($class->lifecycleCallbacks), 2);
        $this->assertEquals($class->lifecycleCallbacks['prePersist'][0], 'doStuffOnPrePersist');
        $this->assertEquals($class->lifecycleCallbacks['postPersist'][0], 'doStuffOnPostPersist');

        return $class;
    }

    /**
     * @depends testLifecycleCallbacks
     * @param ClassMetadata $class
     */
    public function testLifecycleCallbacksSupportMultipleMethodNames($class)
    {
        $this->assertEquals(count($class->lifecycleCallbacks['prePersist']), 2);
        $this->assertEquals($class->lifecycleCallbacks['prePersist'][1], 'doOtherStuffOnPrePersistToo');

        return $class;
    }

    /**
     * @depends testLifecycleCallbacksSupportMultipleMethodNames
     * @param ClassMetadata $class
     */
    public function testCustomFieldName($class)
    {
        $this->assertEquals('name', $class->fieldMappings['name']['fieldName']);
        $this->assertEquals('username', $class->fieldMappings['name']['name']);

        return $class;
    }

    /**
     * @depends testCustomFieldName
     * @param ClassMetadata $class
     */
    public function testDiscriminator($class)
    {
        $this->assertTrue(isset($class->discriminatorField));
        $this->assertTrue(isset($class->discriminatorMap));
        $this->assertEquals(array(
            'fieldName' => 'discr',
            'name' => 'discr',
        ), $class->discriminatorField);
        $this->assertEquals(array(
            'default' => 'Doctrine\ODM\MongoDB\Tests\Mapping\User',
        ), $class->discriminatorMap);

        return $class;
    }

    /**
     * @depends testDiscriminator
     * @param ClassMetadata $class
     */
    public function testEmbedDiscriminator($class)
    {
        $this->assertTrue(isset($class->fieldMappings['otherPhonenumbers']['discriminatorField']));
        $this->assertTrue(isset($class->fieldMappings['otherPhonenumbers']['discriminatorMap']));
        $this->assertEquals('discr', $class->fieldMappings['otherPhonenumbers']['discriminatorField']);
        $this->assertEquals(array(
            'home' => 'Doctrine\ODM\MongoDB\Tests\Mapping\HomePhonenumber',
            'work' => 'Doctrine\ODM\MongoDB\Tests\Mapping\WorkPhonenumber'
        ), $class->fieldMappings['otherPhonenumbers']['discriminatorMap']);

        return $class;
    }

    /**
     * @depends testEmbedDiscriminator
     * @param ClassMetadata $class
     */
    public function testReferenceDiscriminator($class)
    {
        $this->assertTrue(isset($class->fieldMappings['phonenumbers']['discriminatorField']));
        $this->assertTrue(isset($class->fieldMappings['phonenumbers']['discriminatorMap']));
        $this->assertEquals('discr', $class->fieldMappings['phonenumbers']['discriminatorField']);
        $this->assertEquals(array(
            'home' => 'Doctrine\ODM\MongoDB\Tests\Mapping\HomePhonenumber',
            'work' => 'Doctrine\ODM\MongoDB\Tests\Mapping\WorkPhonenumber'
        ), $class->fieldMappings['phonenumbers']['discriminatorMap']);

        return $class;
    }

    /**
     * @depends testCustomFieldName
     * @param ClassMetadata $class
     */
    public function testIndexes($class)
    {
        $this->assertTrue(isset($class->indexes[0]['keys']['username']));
        $this->assertEquals(-1, $class->indexes[0]['keys']['username']);
        $this->assertTrue(isset($class->indexes[0]['options']['unique']));

        $this->assertTrue(isset($class->indexes[1]['keys']['email']));
        $this->assertEquals(-1, $class->indexes[1]['keys']['email']);
        $this->assertTrue( ! empty($class->indexes[1]['options']));
        $this->assertTrue(isset($class->indexes[1]['options']['unique']));
        $this->assertEquals(true, $class->indexes[1]['options']['unique']);
        $this->assertTrue(isset($class->indexes[1]['options']['dropDups']));
        $this->assertEquals(true, $class->indexes[1]['options']['dropDups']);

        $this->assertTrue(isset($class->indexes[2]['keys']['mysqlProfileId']));
        $this->assertEquals(-1, $class->indexes[2]['keys']['mysqlProfileId']);
        $this->assertTrue( ! empty($class->indexes[2]['options']));
        $this->assertTrue(isset($class->indexes[2]['options']['unique']));
        $this->assertEquals(true, $class->indexes[2]['options']['unique']);
        $this->assertTrue(isset($class->indexes[2]['options']['dropDups']));
        $this->assertEquals(true, $class->indexes[2]['options']['dropDups']);

        return $class;
    }
}

/**
 * @ODM\Document(collection="cms_users")
 * @ODM\DiscriminatorField(fieldName="discr")
 * @ODM\DiscriminatorMap({"default"="Doctrine\ODM\MongoDB\Tests\Mapping\User"})
 */
class User
{
    /**
     * @ODM\Id
     */
    public $id;

    /**
     * @ODM\String(name="username")
     * @ODM\Index(order="desc")
     */
    public $name;

    /**
     * @ODM\String
     * @ODM\UniqueIndex(order="desc", dropDups=true)
     */
    public $email;

    /**
     * @ODM\Int
     * @ODM\UniqueIndex(order="desc", dropDups=true)
     */
    public $mysqlProfileId;

    /**
     * @ODM\ReferenceOne(targetDocument="Address", cascade={"remove"})
     */
    public $address;

    /**
     * @ODM\ReferenceMany(targetDocument="Phonenumber", cascade={"persist"}, discriminatorField="discr", discriminatorMap={"home"="HomePhonenumber", "work"="WorkPhonenumber"})
     */
    public $phonenumbers;

    /**
     * @ODM\ReferenceMany(targetDocument="Group", cascade={"all"})
     */
    public $groups;

    /**
     * @ODM\EmbedMany(targetDocument="Phonenumber", discriminatorField="discr", discriminatorMap={"home"="HomePhonenumber", "work"="WorkPhonenumber"})
     */
    public $otherPhonenumbers;

    /**
     * @ODM\PrePersist
     */
    public function doStuffOnPrePersist()
    {
    }

    /**
     * @ODM\PrePersist
     */
    public function doOtherStuffOnPrePersistToo()
    {
    }

    /**
     * @ODM\PostPersist
     */
    public function doStuffOnPostPersist()
    {
    }

    public static function loadMetadata(ClassMetadata $metadata)
    {
        $metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_NONE);
        $metadata->setCollection('cms_users');
        $metadata->addLifecycleCallback('doStuffOnPrePersist', 'prePersist');
        $metadata->addLifecycleCallback('doOtherStuffOnPrePersistToo', 'prePersist');
        $metadata->addLifecycleCallback('doStuffOnPostPersist', 'postPersist');
        $metadata->setDiscriminatorField(array(
            'fieldName' => 'discr',
        ));
        $metadata->setDiscriminatorMap(array(
            'default' => __CLASS__,
        ));
        $metadata->mapField(array(
           'id' => true,
           'fieldName' => 'id',
          ));
        $metadata->mapField(array(
           'fieldName' => 'name',
           'name' => 'username',
           'type' => 'string'
          ));
        $metadata->mapField(array(
           'fieldName' => 'email',
           'type' => 'string'
          ));
          $metadata->mapField(array(
             'fieldName' => 'mysqlProfileId',
             'type' => 'integer'
            ));
        $metadata->mapOneReference(array(
           'fieldName' => 'address',
           'targetDocument' => 'Doctrine\\ODM\\MongoDB\\Tests\\Mapping\\Address',
           'cascade' => 
           array(
           0 => 'remove',
           )
          ));
        $metadata->mapManyReference(array(
           'fieldName' => 'phonenumbers',
           'targetDocument' => 'Doctrine\\ODM\\MongoDB\\Tests\\Mapping\\Phonenumber',
           'cascade' => 
           array(
           1 => 'persist',
           ),
           'discriminatorField' => 'discr',
           'discriminatorMap' => array(
            'home' => 'HomePhonenumber',
            'work' => 'WorkPhonenumber'
           )
          ));
        $metadata->mapManyReference(array(
           'fieldName' => 'groups',
           'targetDocument' => 'Doctrine\\ODM\\MongoDB\\Tests\\Mapping\\Group',
           'cascade' => 
           array(
           0 => 'remove',
           1 => 'persist',
           2 => 'refresh',
           3 => 'merge',
           4 => 'detach',
           ),
          ));
        $metadata->mapManyEmbedded(array(
           'fieldName' => 'otherPhonenumbers',
           'targetDocument' => 'Doctrine\\ODM\\MongoDB\\Tests\\Mapping\\Phonenumber',
           'discriminatorField' => 'discr',
           'discriminatorMap' => array(
            'home' => 'HomePhonenumber',
            'work' => 'WorkPhonenumber'
           )
           )
          );
        $metadata->addIndex(array('username' => 'desc'), array('unique' => true));
        $metadata->addIndex(array('email' => 'desc'), array('unique' => true, 'dropDups' => true));
        $metadata->addIndex(array('mysqlProfileId' => 'desc'), array('unique' => true, 'dropDups' => true));
    }
}