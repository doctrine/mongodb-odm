<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Documents\Feature;

class BuilderTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testPrimeRequiresBooleanOrCallable()
    {
        $this->dm->createQueryBuilder('Documents\User')
            ->field('groups')->prime(1);
    }

    public function testReferencesGoesThroughDiscriminatorMap()
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $q1 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featureFull')->references($f)
            ->getQuery()->debug();

        $this->assertEquals([ 'featureFull.$id' => new \MongoId($f->id) ], $q1['query']);

        $q2 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featureSimple')->references($f)
            ->getQuery()->debug();

        $this->assertEquals([ 'featureSimple' => new \MongoId($f->id) ], $q2['query']);
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"ca"="ChildA", "cb"="ChildB"})
 */
class ParentClass
{
    /** @ODM\Id */
    public $id;
}

/**
 * @ODM\Document
 */
class ChildA extends ParentClass
{
    /** @ODM\ReferenceOne(targetDocument="Documents\Feature") */
    public $featureFull;
}

/**
 * @ODM\Document
 */
class ChildB extends ParentClass
{
    /** @ODM\ReferenceOne(targetDocument="Documents\Feature", simple=true) */
    public $featureSimple;
}
