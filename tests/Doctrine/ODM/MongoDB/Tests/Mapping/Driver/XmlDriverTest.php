<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Driver;

use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\Driver\XmlDriver;
use TestDocuments\UserCustomIdGenerator;
use TestDocuments\UserCustomIdGeneratorWithIdField;

class XmlDriverTest extends AbstractDriverTest
{
    public function setUp()
    {
        $this->driver = new XmlDriver(__DIR__ . '/fixtures/xml');
    }

    public static function getCustomIdGeneratorClasses()
    {
        yield 'legacy-id-attribute' => [UserCustomIdGenerator::class];
        yield 'id-element' => [UserCustomIdGeneratorWithIdField::class];
    }

    /**
     * @dataProvider getCustomIdGeneratorClasses
     */
    public function testDriverShouldReturnOptionsForCustomIdGenerator($className)
    {
        $classMetadata = new ClassMetadata($className);
        $this->driver->loadMetadataForClass($className, $classMetadata);
        $this->assertEquals(array(
            'fieldName' => 'id',
            'strategy' => 'custom',
            'options' => array(
                'class' => 'TestDocuments\CustomIdGenerator',
                'someOption' => 'some-option'
            ),
            'id' => true,
            'name' => '_id',
            'type' => 'custom_id',
            'isCascadeDetach' => false,
            'isCascadeMerge' => false,
            'isCascadePersist' => false,
            'isCascadeRefresh' => false,
            'isCascadeRemove' => false,
            'isInverseSide' => false,
            'isOwningSide' => true,
            'nullable' => false
        ), $classMetadata->fieldMappings['id']);
    }

    public function testDriverShouldParseNonStringAttributes()
    {
        $classMetadata = new ClassMetadata('TestDocuments\UserNonStringOptions');
        $this->driver->loadMetadataForClass('TestDocuments\UserNonStringOptions', $classMetadata);

        $this->assertTrue($classMetadata->requireIndexes);
        $this->assertFalse($classMetadata->slaveOkay);

        $profileMapping = $classMetadata->fieldMappings['profile'];
        $this->assertSame(ClassMetadata::REFERENCE_STORE_AS_ID, $profileMapping['storeAs']);
        $this->assertTrue($profileMapping['orphanRemoval']);

        $profileMapping = $classMetadata->fieldMappings['groups'];
        $this->assertSame(ClassMetadata::REFERENCE_STORE_AS_DB_REF_WITH_DB, $profileMapping['storeAs']);
        $this->assertFalse($profileMapping['orphanRemoval']);
        $this->assertSame(0, $profileMapping['limit']);
        $this->assertSame(2, $profileMapping['skip']);
    }

    public function testInvalidPartialFilterExpressions()
    {
        $classMetadata = new ClassMetadata('TestDocuments\InvalidPartialFilterDocument');
        $this->driver->loadMetadataForClass('TestDocuments\InvalidPartialFilterDocument', $classMetadata);

        $this->assertEquals([
            [
                'keys' => ['fieldA' => 1],
                'options' => [
                    'partialFilterExpression' => [
                        '$and' => [['discr' => ['$eq' => 'default']]],
                    ],
                ],
            ],
            [
                'keys' => ['fieldB' => 1],
                'options' => [],
            ],
        ], $classMetadata->getIndexes());
    }
}

namespace TestDocuments;

class UserCustomIdGenerator
{
    protected $id;
}

class UserCustomIdGeneratorWithIdField
{
    protected $id;
}

class UserNonStringOptions
{
    protected $id;
    protected $profile;
    protected $groups;
}
