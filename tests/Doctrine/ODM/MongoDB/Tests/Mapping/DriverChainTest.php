<?php

namespace Doctrine\ODM\MongoDB\Tests\Mapping;

use Doctrine\ODM\MongoDB\Mapping\Driver\Driver;
use Doctrine\ODM\MongoDB\Mapping\Driver\DriverChain;

class DriverChainTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testDelegateToMatchingNamespaceDriver()
    {
        $className = 'Doctrine\ODM\MongoDB\Tests\Mapping\DriverChainEntity';
        $classMetadata = new \Doctrine\ODM\MongoDB\Mapping\ClassMetadata($className);

        $chain = new DriverChain();

        $driver1 = $this->getMock('Doctrine\ODM\MongoDB\Mapping\Driver\Driver');
        $driver1->expects($this->never())
                ->method('loadMetadataForClass');
        $driver1->expectS($this->never())
                ->method('isTransient');

        $driver2 = $this->getMock('Doctrine\ODM\MongoDB\Mapping\Driver\Driver');
        $driver2->expects($this->at(0))
                ->method('loadMetadataForClass')
                ->with($this->equalTo($className), $this->equalTo($classMetadata));
        $driver2->expects($this->at(1))
                ->method('isTransient')
                ->with($this->equalTo($className))
                ->will($this->returnValue( true ));

        $chain->addDriver($driver1, 'Documents');
        $chain->addDriver($driver2, 'Doctrine\ODM\MongoDB\Tests\Mapping');

        $chain->loadMetadataForClass($className, $classMetadata);

        $this->assertTrue( $chain->isTransient($className) );
    }

    public function testLoadMetadata_NoDelegatorFound_ThrowsMappingException()
    {
        $className = 'Doctrine\ODM\MongoDB\Tests\Mapping\DriverChainEntity';
        $classMetadata = new \Doctrine\ODM\MongoDB\Mapping\ClassMetadata($className);

        $chain = new DriverChain();
        
        $this->setExpectedException('Doctrine\ODM\MongoDB\MongoDBException');
        $chain->loadMetadataForClass($className, $classMetadata);
    }

    public function testGatherAllClassNames()
    {
        $className = 'Doctrine\ODM\MongoDB\Tests\Mapping\DriverChainEntity';
        $classMetadata = new \Doctrine\ODM\MongoDB\Mapping\ClassMetadata($className);

        $chain = new DriverChain();

        $driver1 = $this->getMock('Doctrine\ODM\MongoDB\Mapping\Driver\Driver');
        $driver1->expects($this->once())
                ->method('getAllClassNames')
                ->will($this->returnValue(array('Foo')));

        $driver2 = $this->getMock('Doctrine\ODM\MongoDB\Mapping\Driver\Driver');
        $driver2->expects($this->once())
                ->method('getAllClassNames')
                ->will($this->returnValue(array('Bar', 'Baz')));

        $chain->addDriver($driver1, 'Doctrine\Tests\Models\Company');
        $chain->addDriver($driver2, 'Doctrine\ODM\MongoDB\Tests\Mapping');

        $this->assertEquals(array('Foo', 'Bar', 'Baz'), $chain->getAllClassNames());
    }

    /**
     * @group DDC-706
     */
    public function testIsTransient()
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        
        $chain = new DriverChain();
        $chain->addDriver(new \Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver($reader, array()), 'Documents');

        $this->assertTrue($chain->isTransient('stdClass'), "stdClass isTransient");
        $this->assertFalse($chain->isTransient('Documents\CmsUser'), "CmsUser is not Transient");
    }
}

class DriverChainEntity
{
    
}