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

        $q3 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featurePartial')->references($f)
            ->getQuery()->debug();

        $this->assertEquals(
            [
                'featurePartial.$id' => new \MongoId($f->id),
                'featurePartial.$ref' => 'Feature',
            ],
            $q3['query']
        );
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage No mapping found for field 'nope' in class 'Doctrine\ODM\MongoDB\Tests\Query\ParentClass' nor its descendants.
     */
    public function testReferencesThrowsSpecializedExceptionForDiscriminatedDocuments()
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $this->dm->createQueryBuilder(ParentClass::class)
            ->field('nope')->references($f)
            ->getQuery();
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage Reference mapping for field 'conflict' in class 'Doctrine\ODM\MongoDB\Tests\Query\ChildA' conflicts with one mapped in class 'Doctrine\ODM\MongoDB\Tests\Query\ChildB'.
     */
    public function testReferencesThrowsSpecializedExceptionForConflictingMappings()
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $this->dm->createQueryBuilder(ParentClass::class)
            ->field('conflict')->references($f)
            ->getQuery();
    }

    public function testIncludesReferenceToGoesThroughDiscriminatorMap()
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $q1 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featureFullMany')->includesReferenceTo($f)
            ->getQuery()->debug();

        $this->assertEquals([ 'featureFullMany' => [ '$elemMatch' => [ '$id' => new \MongoId($f->id) ] ] ], $q1['query']);

        $q2 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featureSimpleMany')->includesReferenceTo($f)
            ->getQuery()->debug();

        $this->assertEquals([ 'featureSimpleMany' => new \MongoId($f->id) ], $q2['query']);

        $q3 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featurePartialMany')->includesReferenceTo($f)
            ->getQuery()->debug();

        $this->assertEquals(
            [
                'featurePartialMany' => [
                    '$elemMatch' => [
                        '$id' => new \MongoId($f->id),
                        '$ref' => 'Feature',
                    ]
                ]
            ],
            $q3['query']
        );
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage No mapping found for field 'nope' in class 'Doctrine\ODM\MongoDB\Tests\Query\ParentClass' nor its descendants.
     */
    public function testIncludesReferenceToThrowsSpecializedExceptionForDiscriminatedDocuments()
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $this->dm->createQueryBuilder(ParentClass::class)
            ->field('nope')->includesReferenceTo($f)
            ->getQuery();
    }

    /**
     * @expectedException \Doctrine\ODM\MongoDB\Mapping\MappingException
     * @expectedExceptionMessage Reference mapping for field 'conflictMany' in class 'Doctrine\ODM\MongoDB\Tests\Query\ChildA' conflicts with one mapped in class 'Doctrine\ODM\MongoDB\Tests\Query\ChildB'.
     */
    public function testIncludesReferenceToThrowsSpecializedExceptionForConflictingMappings()
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $this->dm->createQueryBuilder(ParentClass::class)
            ->field('conflictMany')->includesReferenceTo($f)
            ->getQuery();
    }

    /**
     * @dataProvider provideArrayUpdateOperatorsOnReferenceMany
     */
    public function testArrayUpdateOperatorsOnReferenceMany($class, $field)
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $q1 = $this->dm->createQueryBuilder($class)
            ->findAndUpdate()
            ->field($field)->addToSet($f)
            ->getQuery()->debug();

        $expected = $this->dm->createDBRef($f, $this->dm->getClassMetadata($class)->fieldMappings[$field]);
        $this->assertEquals($expected, $q1['newObj']['$addToSet'][$field]);
    }

    public function provideArrayUpdateOperatorsOnReferenceMany()
    {
        yield [ChildA::class, 'featureFullMany'];
        yield [ChildB::class, 'featureSimpleMany'];
        yield [ChildC::class, 'featurePartialMany'];
    }

    /**
     * @dataProvider provideArrayUpdateOperatorsOnReferenceOne
     */
    public function testArrayUpdateOperatorsOnReferenceOne($class, $field)
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $q1 = $this->dm->createQueryBuilder($class)
            ->findAndUpdate()
            ->field($field)->set($f)
            ->getQuery()->debug();

        $expected = $this->dm->createDBRef($f, $this->dm->getClassMetadata($class)->fieldMappings[$field]);
        $this->assertEquals($expected, $q1['newObj']['$set'][$field]);
    }

    public function provideArrayUpdateOperatorsOnReferenceOne()
    {
        yield [ChildA::class, 'featureFull'];
        yield [ChildB::class, 'featureSimple'];
        yield [ChildC::class, 'featurePartial'];
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField(fieldName="type")
 * @ODM\DiscriminatorMap({"ca"="ChildA", "cb"="ChildB", "cc"="ChildC"})
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

    /** @ODM\ReferenceMany(targetDocument="Documents\Feature") */
    public $featureFullMany;

    /** @ODM\ReferenceOne(targetDocument="Documents\Feature") */
    public $conflict;

    /** @ODM\ReferenceMany(targetDocument="Documents\Feature") */
    public $conflictMany;
}

/**
 * @ODM\Document
 */
class ChildB extends ParentClass
{
    /** @ODM\ReferenceOne(targetDocument="Documents\Feature", simple=true) */
    public $featureSimple;

    /** @ODM\ReferenceMany(targetDocument="Documents\Feature", simple=true) */
    public $featureSimpleMany;

    /** @ODM\ReferenceOne(targetDocument="Documents\Feature", simple=true) */
    public $conflict;

    /** @ODM\ReferenceMany(targetDocument="Documents\Feature", simple=true) */
    public $conflictMany;
}

/**
 * @ODM\Document
 */
class ChildC extends ParentClass
{
    /** @ODM\ReferenceOne(storeAs="dbRef") */
    public $featurePartial;

    /** @ODM\ReferenceMany(storeAs="dbRef") */
    public $featurePartialMany;
}
