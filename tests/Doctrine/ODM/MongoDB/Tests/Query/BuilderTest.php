<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Query;

use DateTime;
use Doctrine\ODM\MongoDB\Iterator\UnrewindableIterator;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Expr;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Doctrine\ODM\MongoDB\Types\Type;
use Documents\Feature;
use Documents\User;
use GeoJson\Geometry\Geometry;
use GeoJson\Geometry\Point;
use InvalidArgumentException;
use IteratorAggregate;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\Driver\ReadPreference;
use ReflectionProperty;

class BuilderTest extends BaseTest
{
    public function testPrimeRequiresBooleanOrCallable()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->createQueryBuilder(User::class)
            ->field('groups')->prime(1);
    }

    public function testReferencesGoesThroughDiscriminatorMap()
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $q1 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featureFull')->references($f)
            ->getQuery()->debug();

        $this->assertEquals(
            [
                'featureFull.$id' => new ObjectId($f->id),
                'type' => ['$in' => ['ca', 'cb', 'cc']],
            ],
            $q1['query']
        );

        $q2 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featureSimple')->references($f)
            ->getQuery()->debug();

        $this->assertEquals(
            [
                'featureSimple' => new ObjectId($f->id),
                'type' => ['$in' => ['ca', 'cb', 'cc']],
            ],
            $q2['query']
        );

        $q3 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featurePartial')->references($f)
            ->getQuery()->debug();

        $this->assertEquals(
            [
                'featurePartial.$id' => new ObjectId($f->id),
                'featurePartial.$ref' => 'Feature',
                'type' => ['$in' => ['ca', 'cb', 'cc']],
            ],
            $q3['query']
        );
    }

    public function testReferencesThrowsSpecializedExceptionForDiscriminatedDocuments()
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'No mapping found for field \'nope\' in class \'Doctrine\ODM\MongoDB\Tests\Query\ParentClass\' nor ' .
            'its descendants.'
        );
        $this->dm->createQueryBuilder(ParentClass::class)
            ->field('nope')->references($f)
            ->getQuery();
    }

    public function testReferencesThrowsSpecializedExceptionForConflictingMappings()
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Reference mapping for field \'conflict\' in class \'Doctrine\ODM\MongoDB\Tests\Query\ChildA\' ' .
            'conflicts with one mapped in class \'Doctrine\ODM\MongoDB\Tests\Query\ChildB\'.'
        );
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

        $this->assertEquals(
            [
                'featureFullMany' => [
                    '$elemMatch' => ['$id' => new ObjectId($f->id)],
                ],
                'type' => ['$in' => ['ca', 'cb', 'cc']],
            ],
            $q1['query']
        );

        $q2 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featureSimpleMany')->includesReferenceTo($f)
            ->getQuery()->debug();

        $this->assertEquals(
            [
                'featureSimpleMany' => new ObjectId($f->id),
                'type' => ['$in' => ['ca', 'cb', 'cc']],
            ],
            $q2['query']
        );

        $q3 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featurePartialMany')->includesReferenceTo($f)
            ->getQuery()->debug();

        $this->assertEquals(
            [
                'featurePartialMany' => [
                    '$elemMatch' => [
                        '$id' => new ObjectId($f->id),
                        '$ref' => 'Feature',
                    ],
                ],
                'type' => ['$in' => ['ca', 'cb', 'cc']],
            ],
            $q3['query']
        );
    }

    public function testIncludesReferenceToThrowsSpecializedExceptionForDiscriminatedDocuments()
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'No mapping found for field \'nope\' in class \'Doctrine\ODM\MongoDB\Tests\Query\ParentClass\' nor ' .
            'its descendants.'
        );
        $this->dm->createQueryBuilder(ParentClass::class)
            ->field('nope')->includesReferenceTo($f)
            ->getQuery();
    }

    public function testIncludesReferenceToThrowsSpecializedExceptionForConflictingMappings()
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Reference mapping for field \'conflictMany\' in class \'Doctrine\ODM\MongoDB\Tests\Query\ChildA\' ' .
            'conflicts with one mapped in class \'Doctrine\ODM\MongoDB\Tests\Query\ChildB\'.'
        );
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

        $expected = $this->dm->createReference($f, $this->dm->getClassMetadata($class)->fieldMappings[$field]);
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

        $expected = $this->dm->createReference($f, $this->dm->getClassMetadata($class)->fieldMappings[$field]);
        $this->assertEquals($expected, $q1['newObj']['$set'][$field]);
    }

    public function provideArrayUpdateOperatorsOnReferenceOne()
    {
        yield [ChildA::class, 'featureFull'];
        yield [ChildB::class, 'featureSimple'];
        yield [ChildC::class, 'featurePartial'];
    }

    public function testThatOrAcceptsAnotherQuery()
    {
        $qb = $this->getTestQueryBuilder();
        $qb->addOr($qb->expr()->field('firstName')->equals('Kris'));
        $qb->addOr($qb->expr()->field('firstName')->equals('Chris'));

        $this->assertEquals([
            '$or' => [
                ['firstName' => 'Kris'],
                ['firstName' => 'Chris'],
            ],
        ], $qb->getQueryArray());
    }

    public function testThatAndAcceptsAnotherQuery()
    {
        $qb = $this->getTestQueryBuilder();
        $qb->addAnd($qb->expr()->field('hits')->gte(1));
        $qb->addAnd($qb->expr()->field('hits')->lt(5));

        $this->assertEquals([
            '$and' => [
                ['hits' => ['$gte' => 1]],
                ['hits' => ['$lt' => 5]],
            ],
        ], $qb->getQueryArray());
    }

    public function testThatNorAcceptsAnotherQuery()
    {
        $qb = $this->getTestQueryBuilder();
        $qb->addNor($qb->expr()->field('firstName')->equals('Kris'));
        $qb->addNor($qb->expr()->field('firstName')->equals('Chris'));

        $this->assertEquals([
            '$nor' => [
                ['firstName' => 'Kris'],
                ['firstName' => 'Chris'],
            ],
        ], $qb->getQueryArray());
    }

    public function testAddElemMatch()
    {
        $qb = $this->getTestQueryBuilder();
        $qb->field('phonenumbers')->elemMatch($qb->expr()->field('phonenumber')->equals('6155139185'));
        $expected = [
            'phonenumbers' => [
                '$elemMatch' => ['phonenumber' => '6155139185'],
            ],
        ];
        $this->assertEquals($expected, $qb->getQueryArray());
    }

    public function testAddNot()
    {
        $qb = $this->getTestQueryBuilder();
        $qb->field('username')->not($qb->expr()->in(['boo']));
        $expected = [
            'username' => [
                '$not' => [
                    '$in' => ['boo'],
                ],
            ],
        ];
        $this->assertEquals($expected, $qb->getQueryArray());
    }

    public function testNotAllowsRegex()
    {
        $qb = $this->getTestQueryBuilder();
        $qb->field('username')->not(new Regex('Boo', 'i'));

        $expected = [
            'username' => [
                '$not' => new Regex('Boo', 'i'),
            ],
        ];
        $this->assertEquals($expected, $qb->getQueryArray());
    }

    public function testFindQuery()
    {
        $qb       = $this->getTestQueryBuilder()
            ->where("function() { return this.username == 'boo' }");
        $expected = ['$where' => "function() { return this.username == 'boo' }"];
        $this->assertEquals($expected, $qb->getQueryArray());
    }

    public function testUpsertUpdateQuery()
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->upsert(true)
            ->field('username')->set('jwage');

        $expected = [
            '$set' => ['username' => 'jwage'],
        ];
        $this->assertEquals($expected, $qb->getNewObj());
        $this->assertTrue($qb->debug('upsert'));
    }

    public function testMultipleUpdateQuery()
    {
        $qb = $this->getTestQueryBuilder()
            ->updateMany()
            ->field('username')->set('jwage');

        $expected = [
            '$set' => ['username' => 'jwage'],
        ];
        $this->assertEquals($expected, $qb->getNewObj());
        $this->assertTrue($qb->debug('multiple'));
    }

    public function testComplexUpdateQuery()
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('username')
            ->set('jwage')
            ->equals('boo');

        $expected = ['username' => 'boo'];
        $this->assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$set' => ['username' => 'jwage'],
        ];
        $this->assertEquals($expected, $qb->getNewObj());
    }

    public function testIncUpdateQuery()
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('hits')->inc(5)
            ->field('username')->equals('boo');

        $expected = ['username' => 'boo'];
        $this->assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$inc' => ['hits' => 5],
        ];
        $this->assertEquals($expected, $qb->getNewObj());
    }

    public function testUnsetField()
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('hits')->unsetField()
            ->field('username')->equals('boo');

        $expected = ['username' => 'boo'];
        $this->assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$unset' => ['hits' => 1],
        ];
        $this->assertEquals($expected, $qb->getNewObj());
    }

    public function testSetOnInsert()
    {
        $createDate = new DateTime();
        $qb         = $this->getTestQueryBuilder()
            ->updateOne()
            ->upsert()
            ->field('username')->equals('boo')
            ->field('createDate')->setOnInsert($createDate);

        $expected = ['username' => 'boo'];
        $this->assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$setOnInsert' => [
                'createDate' => Type::getType('date')->convertToDatabaseValue($createDate),
            ],
        ];
        $this->assertEquals($expected, $qb->getNewObj());
    }

    public function testDateRange()
    {
        $start = new DateTime('1985-09-01 01:00:00');
        $end   = new DateTime('1985-09-04');
        $qb    = $this->getTestQueryBuilder();
        $qb->field('createdAt')->range($start, $end);

        $expected = [
            'createdAt' => [
                '$gte' => Type::getType('date')->convertToDatabaseValue($start),
                '$lt' => Type::getType('date')->convertToDatabaseValue($end),
            ],
        ];
        $this->assertEquals($expected, $qb->getQueryArray());
    }

    public function testQueryIsIterable()
    {
        $qb    = $this->getTestQueryBuilder();
        $query = $qb->getQuery();
        $this->assertInstanceOf(IteratorAggregate::class, $query);
    }

    public function testDeepClone()
    {
        $qb = $this->getTestQueryBuilder();

        $qb->field('username')->equals('jwage');

        $this->assertCount(1, $qb->getQueryArray());

        $qb2 = clone $qb;
        $qb2->field('firstName')->equals('Jon');

        $this->assertCount(1, $qb->getQueryArray());
    }

    /**
     * @dataProvider provideProxiedExprMethods
     */
    public function testProxiedExprMethods($method, array $args = [])
    {
        $expr = $this->getMockExpr();
        $expr
            ->expects($this->once())
            ->method($method)
            ->with(...$args);

        $qb                 = $this->getTestQueryBuilder();
        $reflectionProperty = new ReflectionProperty($qb, 'expr');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($qb, $expr);

        $this->assertSame($qb, $qb->$method(...$args));
    }

    public function provideProxiedExprMethods()
    {
        return [
            'field()' => ['field', ['fieldName']],
            'equals()' => ['equals', ['value']],
            'where()' => ['where', ['this.fieldName == 1']],
            'in()' => ['in', [['value1', 'value2']]],
            'notIn()' => ['notIn', [['value1', 'value2']]],
            'notEqual()' => ['notEqual', ['value']],
            'gt()' => ['gt', [1]],
            'gte()' => ['gte', [1]],
            'lt()' => ['gt', [1]],
            'lte()' => ['gte', [1]],
            'range()' => ['range', [0, 1]],
            'size()' => ['size', [1]],
            'exists()' => ['exists', [true]],
            'type()' => ['type', [7]],
            'all()' => ['all', [['value1', 'value2']]],
            'mod()' => ['mod', [2, 0]],
            'near()' => ['near', [1, 2]],
            'nearSphere()' => ['nearSphere', [1, 2]],
            'geoIntersects()' => ['geoIntersects', [$this->getMockGeometry()]],
            'geoWithin()' => ['geoWithin', [$this->getMockGeometry()]],
            'geoWithinBox()' => ['geoWithinBox', [1, 2, 3, 4]],
            'geoWithinCenter()' => ['geoWithinCenter', [1, 2, 3]],
            'geoWithinCenterSphere()' => ['geoWithinCenterSphere', [1, 2, 3]],
            'geoWithinPolygon()' => ['geoWithinPolygon', [[0, 0], [1, 1], [1, 0]]],
            'inc()' => ['inc', [1]],
            'mul()' => ['mul', [1]],
            'unsetField()' => ['unsetField'],
            'setOnInsert()' => ['setOnInsert', [1]],
            'push() with value' => ['push', ['value']],
            'push() with Expr' => ['push', [$this->getMockExpr()]],
            'addToSet() with value' => ['addToSet', ['value']],
            'addToSet() with Expr' => ['addToSet', [$this->getMockExpr()]],
            'popFirst()' => ['popFirst'],
            'popLast()' => ['popLast'],
            'pull()' => ['pull', ['value']],
            'pullAll()' => ['pullAll', [['value1', 'value2']]],
            'addAnd() array' => ['addAnd', [[]]],
            'addAnd() Expr' => ['addAnd', [$this->getMockExpr()]],
            'addOr() array' => ['addOr', [[]]],
            'addOr() Expr' => ['addOr', [$this->getMockExpr()]],
            'addNor() array' => ['addNor', [[]]],
            'addNor() Expr' => ['addNor', [$this->getMockExpr()]],
            'elemMatch() array' => ['elemMatch', [[]]],
            'elemMatch() Expr' => ['elemMatch', [$this->getMockExpr()]],
            'not()' => ['not', [$this->getMockExpr()]],
            'language()' => ['language', ['en']],
            'caseSensitive()' => ['caseSensitive', [true]],
            'diacriticSensitive()' => ['diacriticSensitive', [true]],
            'text()' => ['text', ['foo']],
            'max()' => ['max', [1]],
            'min()' => ['min', [1]],
            'comment()' => ['comment', ['A comment explaining what the query does']],
            'bitsAllClear()' => ['bitsAllClear', [5]],
            'bitsAllSet()' => ['bitsAllSet', [5]],
            'bitsAnyClear()' => ['bitsAnyClear', [5]],
            'bitsAnySet()' => ['bitsAnySet', [5]],
        ];
    }

    public function providePoint()
    {
        $coordinates = [0, 0];
        $json        = ['type' => 'Point', 'coordinates' => $coordinates];

        return [
            'legacy array' => [$coordinates, $coordinates, false],
            'GeoJSON array' => [$json, $json, true],
            'GeoJSON object' => [$this->getMockPoint($json), $json, true],
        ];
    }

    /**
     * @dataProvider provideSelectProjections
     */
    public function testSelect(array $args, array $expected)
    {
        $qb = $this->getTestQueryBuilder();
        $qb->select(...$args);

        $this->assertEquals($expected, $qb->debug('select'));
    }

    public function provideSelectProjections()
    {
        return $this->provideProjections(true);
    }

    /**
     * @dataProvider provideExcludeProjections
     */
    public function testExclude(array $args, array $expected)
    {
        $qb = $this->getTestQueryBuilder();
        $qb->exclude(...$args);

        $this->assertEquals($expected, $qb->debug('select'));
    }

    public function provideExcludeProjections()
    {
        return $this->provideProjections(false);
    }

    /**
     * Provide arguments for select() and exclude() tests.
     *
     * @param bool $include Whether the field should be included or excluded
     *
     * @return array
     */
    private function provideProjections($include)
    {
        $project = $include ? 1 : 0;

        return [
            'multiple arguments' => [
                ['foo', 'bar'],
                ['foo' => $project, 'bar' => $project],
            ],
            'no arguments' => [
                [],
                [],
            ],
            'array argument' => [
                [['foo', 'bar']],
                ['foo' => $project, 'bar' => $project],
            ],
            'empty array' => [
                [[]],
                [],
            ],
        ];
    }

    public function testSelectSliceWithCount()
    {
        $qb = $this->getTestQueryBuilder()
            ->selectSlice('tags', 10);

        $expected = ['tags' => ['$slice' => 10]];

        $this->assertEquals($expected, $qb->debug('select'));
    }

    public function testSelectSliceWithSkipAndLimit()
    {
        $qb = $this->getTestQueryBuilder()
            ->selectSlice('tags', -5, 5);

        $expected = ['tags' => ['$slice' => [-5, 5]]];

        $this->assertEquals($expected, $qb->debug('select'));
    }

    public function testSelectElemMatchWithArray()
    {
        $qb = $this->getTestQueryBuilder()
            ->selectElemMatch('addresses', ['state' => 'ny']);

        $expected = ['addresses' => ['$elemMatch' => ['state' => 'ny']]];

        $this->assertEquals($expected, $qb->debug('select'));
    }

    public function testSelectElemMatchWithExpr()
    {
        $qb = $this->getTestQueryBuilder();
        $qb->selectElemMatch('addresses', $qb->expr()->field('state')->equals('ny'));

        $expected = ['addresses' => ['$elemMatch' => ['state' => 'ny']]];

        $this->assertEquals($expected, $qb->debug('select'));
    }

    public function testSelectMeta()
    {
        $qb = $this->getTestQueryBuilder()
            ->selectMeta('score', 'textScore');

        $expected = ['score' => ['$meta' => 'textScore']];

        $this->assertEquals($expected, $qb->debug('select'));
    }

    public function testSetReadPreference()
    {
        $qb = $this->getTestQueryBuilder();
        $qb->setReadPreference(new ReadPreference('secondary', [['dc' => 'east']]));

        $readPreference = $qb->debug('readPreference');
        $this->assertInstanceOf(ReadPreference::class, $readPreference);
        $this->assertEquals(ReadPreference::RP_SECONDARY, $readPreference->getMode());
        $this->assertEquals([['dc' => 'east']], $readPreference->getTagSets());
    }

    public function testSortWithFieldNameAndDefaultOrder()
    {
        $qb = $this->getTestQueryBuilder()
            ->sort('foo');

        $this->assertEquals(['foo' => 1], $qb->debug('sort'));
    }

    /**
     * @dataProvider provideSortOrders
     */
    public function testSortWithFieldNameAndOrder($order, $expectedOrder)
    {
        $qb = $this->getTestQueryBuilder()
            ->sort('foo', $order);

        $this->assertEquals(['foo' => $expectedOrder], $qb->debug('sort'));
    }

    public function provideSortOrders()
    {
        return [
            [1, 1],
            [-1, -1],
            ['asc', 1],
            ['desc', -1],
            ['ASC', 1],
            ['DESC', -1],
        ];
    }

    public function testSortWithArrayOfFieldNameAndOrderPairs()
    {
        $qb = $this->getTestQueryBuilder()
            ->sort(['foo' => 1, 'bar' => -1]);

        $this->assertEquals(['foo' => 1, 'bar' => -1], $qb->debug('sort'));
    }

    public function testSortMetaDoesProjectMissingField()
    {
        $qb = $this->getTestQueryBuilder()
            ->select('score')
            ->sortMeta('score', 'textScore');

        /* This will likely yield a server error, but sortMeta() should only set
         * the projection if it doesn't already exist.
         */
        $this->assertEquals(['score' => 1], $qb->debug('select'));
        $this->assertEquals(['score' => ['$meta' => 'textScore']], $qb->debug('sort'));
    }

    public function testSortMetaDoesNotProjectExistingField()
    {
        $qb = $this->getTestQueryBuilder()
            ->sortMeta('score', 'textScore');

        $this->assertEquals(['score' => ['$meta' => 'textScore']], $qb->debug('select'));
        $this->assertEquals(['score' => ['$meta' => 'textScore']], $qb->debug('sort'));
    }

    /**
     * @dataProvider provideCurrentDateOptions
     */
    public function testCurrentDateUpdateQuery($type)
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('lastUpdated')->currentDate($type)
            ->field('username')->equals('boo');

        $expected = ['username' => 'boo'];
        $this->assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$currentDate' => [
                'lastUpdated' => ['$type' => $type],
            ],
        ];
        $this->assertEquals($expected, $qb->getNewObj());
    }

    public static function provideCurrentDateOptions()
    {
        return [
            ['date'],
            ['timestamp'],
        ];
    }

    public function testCurrentDateInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getTestQueryBuilder()
            ->updateOne()
            ->field('lastUpdated')->currentDate('notADate');
    }

    public function testBitAndUpdateQuery()
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('flags')->bitAnd(15)
            ->field('username')->equals('boo');

        $expected = ['username' => 'boo'];
        $this->assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$bit' => [
                'flags' => ['and' => 15],
            ],
        ];
        $this->assertEquals($expected, $qb->getNewObj());
    }

    public function testBitOrUpdateQuery()
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('flags')->bitOr(15)
            ->field('username')->equals('boo');

        $expected = ['username' => 'boo'];
        $this->assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$bit' => [
                'flags' => ['or' => 15],
            ],
        ];
        $this->assertEquals($expected, $qb->getNewObj());
    }

    public function testBitXorUpdateQuery()
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('flags')->bitXor(15)
            ->field('username')->equals('boo');

        $expected = ['username' => 'boo'];
        $this->assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$bit' => [
                'flags' => ['xor' => 15],
            ],
        ];
        $this->assertEquals($expected, $qb->getNewObj());
    }

    public function testNonRewindable()
    {
        $query = $this->getTestQueryBuilder()
            ->setRewindable(false)
            ->getQuery();

        $this->assertInstanceOf(UnrewindableIterator::class, $query->execute());
    }

    private function getTestQueryBuilder()
    {
        return new Builder($this->dm, User::class);
    }

    private function getMockExpr()
    {
        return $this->getMockBuilder(Expr::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockGeometry()
    {
        return $this->getMockBuilder(Geometry::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockPoint($json)
    {
        $point = $this->getMockBuilder(Point::class)
            ->disableOriginalConstructor()
            ->getMock();

        $point->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn($json);

        return $point;
    }
}

/**
 * @ODM\Document
 * @ODM\InheritanceType("SINGLE_COLLECTION")
 * @ODM\DiscriminatorField("type")
 * @ODM\DiscriminatorMap({"ca"=ChildA::class, "cb"=ChildB::class, "cc"=ChildC::class})
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
    /** @ODM\ReferenceOne(targetDocument=Documents\Feature::class) */
    public $featureFull;

    /** @ODM\ReferenceMany(targetDocument=Documents\Feature::class) */
    public $featureFullMany;

    /** @ODM\ReferenceOne(targetDocument=Documents\Feature::class) */
    public $conflict;

    /** @ODM\ReferenceMany(targetDocument=Documents\Feature::class) */
    public $conflictMany;
}

/**
 * @ODM\Document
 */
class ChildB extends ParentClass
{
    /** @ODM\ReferenceOne(targetDocument=Documents\Feature::class, storeAs="id") */
    public $featureSimple;

    /** @ODM\ReferenceMany(targetDocument=Documents\Feature::class, storeAs="id") */
    public $featureSimpleMany;

    /** @ODM\ReferenceOne(targetDocument=Documents\Feature::class, storeAs="id") */
    public $conflict;

    /** @ODM\ReferenceMany(targetDocument=Documents\Feature::class, storeAs="id") */
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
