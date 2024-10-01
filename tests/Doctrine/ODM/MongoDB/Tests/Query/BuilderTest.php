<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Query;

use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\ODM\MongoDB\Iterator\UnrewindableIterator;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Expr;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use Doctrine\ODM\MongoDB\Types\Type;
use Documents\Feature;
use Documents\User;
use Generator;
use GeoJson\Geometry\Geometry;
use GeoJson\Geometry\Point;
use InvalidArgumentException;
use IteratorAggregate;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\Driver\ReadPreference;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;

class BuilderTest extends BaseTestCase
{
    public function testPrimeRequiresBooleanOrCallable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->dm->createQueryBuilder(User::class)
            ->field('groups')->prime(1);
    }

    public function testReferencesGoesThroughDiscriminatorMap(): void
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $q1 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featureFull')->references($f)
            ->getQuery()->debug();

        self::assertEquals(
            [
                'featureFull.$id' => new ObjectId($f->id),
                'type' => ['$in' => ['ca', 'cb', 'cc']],
            ],
            $q1['query'],
        );

        $q2 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featureSimple')->references($f)
            ->getQuery()->debug();

        self::assertEquals(
            [
                'featureSimple' => new ObjectId($f->id),
                'type' => ['$in' => ['ca', 'cb', 'cc']],
            ],
            $q2['query'],
        );

        $q3 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featurePartial')->references($f)
            ->getQuery()->debug();

        self::assertEquals(
            [
                'featurePartial.$id' => new ObjectId($f->id),
                'featurePartial.$ref' => 'Feature',
                'type' => ['$in' => ['ca', 'cb', 'cc']],
            ],
            $q3['query'],
        );
    }

    public function testReferencesThrowsSpecializedExceptionForDiscriminatedDocuments(): void
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'No mapping found for field \'nope\' in class \'Doctrine\ODM\MongoDB\Tests\Query\ParentClass\' nor ' .
            'its descendants.',
        );
        $this->dm->createQueryBuilder(ParentClass::class)
            ->field('nope')->references($f)
            ->getQuery();
    }

    public function testReferencesThrowsSpecializedExceptionForConflictingMappings(): void
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Reference mapping for field \'conflict\' in class \'Doctrine\ODM\MongoDB\Tests\Query\ChildA\' ' .
            'conflicts with one mapped in class \'Doctrine\ODM\MongoDB\Tests\Query\ChildB\'.',
        );
        $this->dm->createQueryBuilder(ParentClass::class)
            ->field('conflict')->references($f)
            ->getQuery();
    }

    public function testIncludesReferenceToGoesThroughDiscriminatorMap(): void
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $q1 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featureFullMany')->includesReferenceTo($f)
            ->getQuery()->debug();

        self::assertEquals(
            [
                'featureFullMany' => [
                    '$elemMatch' => ['$id' => new ObjectId($f->id)],
                ],
                'type' => ['$in' => ['ca', 'cb', 'cc']],
            ],
            $q1['query'],
        );

        $q2 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featureSimpleMany')->includesReferenceTo($f)
            ->getQuery()->debug();

        self::assertEquals(
            [
                'featureSimpleMany' => new ObjectId($f->id),
                'type' => ['$in' => ['ca', 'cb', 'cc']],
            ],
            $q2['query'],
        );

        $q3 = $this->dm->createQueryBuilder(ParentClass::class)
            ->field('featurePartialMany')->includesReferenceTo($f)
            ->getQuery()->debug();

        self::assertEquals(
            [
                'featurePartialMany' => [
                    '$elemMatch' => [
                        '$id' => new ObjectId($f->id),
                        '$ref' => 'Feature',
                    ],
                ],
                'type' => ['$in' => ['ca', 'cb', 'cc']],
            ],
            $q3['query'],
        );
    }

    public function testIncludesReferenceToThrowsSpecializedExceptionForDiscriminatedDocuments(): void
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'No mapping found for field \'nope\' in class \'Doctrine\ODM\MongoDB\Tests\Query\ParentClass\' nor ' .
            'its descendants.',
        );
        $this->dm->createQueryBuilder(ParentClass::class)
            ->field('nope')->includesReferenceTo($f)
            ->getQuery();
    }

    public function testIncludesReferenceToThrowsSpecializedExceptionForConflictingMappings(): void
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Reference mapping for field \'conflictMany\' in class \'Doctrine\ODM\MongoDB\Tests\Query\ChildA\' ' .
            'conflicts with one mapped in class \'Doctrine\ODM\MongoDB\Tests\Query\ChildB\'.',
        );
        $this->dm->createQueryBuilder(ParentClass::class)
            ->field('conflictMany')->includesReferenceTo($f)
            ->getQuery();
    }

    /** @param class-string $class */
    #[DataProvider('provideArrayUpdateOperatorsOnReferenceMany')]
    public function testArrayUpdateOperatorsOnReferenceMany(string $class, string $field): void
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $q1 = $this->dm->createQueryBuilder($class)
            ->findAndUpdate()
            ->field($field)->addToSet($f)
            ->getQuery()->debug();

        $expected = $this->dm->createReference($f, $this->dm->getClassMetadata($class)->fieldMappings[$field]);
        self::assertEquals($expected, $q1['newObj']['$addToSet'][$field]);
    }

    public static function provideArrayUpdateOperatorsOnReferenceMany(): Generator
    {
        yield [ChildA::class, 'featureFullMany'];
        yield [ChildB::class, 'featureSimpleMany'];
        yield [ChildC::class, 'featurePartialMany'];
    }

    /** @param class-string $class */
    #[DataProvider('provideArrayUpdateOperatorsOnReferenceOne')]
    public function testArrayUpdateOperatorsOnReferenceOne(string $class, string $field): void
    {
        $f = new Feature('Smarter references');
        $this->dm->persist($f);

        $q1 = $this->dm->createQueryBuilder($class)
            ->findAndUpdate()
            ->field($field)->set($f)
            ->getQuery()->debug();

        $expected = $this->dm->createReference($f, $this->dm->getClassMetadata($class)->fieldMappings[$field]);
        self::assertEquals($expected, $q1['newObj']['$set'][$field]);
    }

    public static function provideArrayUpdateOperatorsOnReferenceOne(): Generator
    {
        yield [ChildA::class, 'featureFull'];
        yield [ChildB::class, 'featureSimple'];
        yield [ChildC::class, 'featurePartial'];
    }

    public function testThatOrAcceptsAnotherQuery(): void
    {
        $qb = $this->getTestQueryBuilder();
        $qb->addOr($qb->expr()->field('firstName')->equals('Kris'));
        $qb->addOr($qb->expr()->field('firstName')->equals('Chris'));

        self::assertEquals([
            '$or' => [
                ['firstName' => 'Kris'],
                ['firstName' => 'Chris'],
            ],
        ], $qb->getQueryArray());
    }

    public function testThatAndAcceptsAnotherQuery(): void
    {
        $qb = $this->getTestQueryBuilder();
        $qb->addAnd($qb->expr()->field('hits')->gte(1));
        $qb->addAnd($qb->expr()->field('hits')->lt(5));

        self::assertEquals([
            '$and' => [
                ['hits' => ['$gte' => 1]],
                ['hits' => ['$lt' => 5]],
            ],
        ], $qb->getQueryArray());
    }

    public function testThatNorAcceptsAnotherQuery(): void
    {
        $qb = $this->getTestQueryBuilder();
        $qb->addNor($qb->expr()->field('firstName')->equals('Kris'));
        $qb->addNor($qb->expr()->field('firstName')->equals('Chris'));

        self::assertEquals([
            '$nor' => [
                ['firstName' => 'Kris'],
                ['firstName' => 'Chris'],
            ],
        ], $qb->getQueryArray());
    }

    public function testAddElemMatch(): void
    {
        $qb = $this->getTestQueryBuilder();
        $qb->field('phonenumbers')->elemMatch($qb->expr()->field('phonenumber')->equals('6155139185'));
        $expected = [
            'phonenumbers' => [
                '$elemMatch' => ['phonenumber' => '6155139185'],
            ],
        ];
        self::assertEquals($expected, $qb->getQueryArray());
    }

    public function testAddNot(): void
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
        self::assertEquals($expected, $qb->getQueryArray());
    }

    public function testNotAllowsRegex(): void
    {
        $qb = $this->getTestQueryBuilder();
        $qb->field('username')->not(new Regex('Boo', 'i'));

        $expected = [
            'username' => [
                '$not' => new Regex('Boo', 'i'),
            ],
        ];
        self::assertEquals($expected, $qb->getQueryArray());
    }

    public function testFindQuery(): void
    {
        $qb       = $this->getTestQueryBuilder()
            ->where("function() { return this.username == 'boo' }");
        $expected = ['$where' => "function() { return this.username == 'boo' }"];
        self::assertEquals($expected, $qb->getQueryArray());
    }

    public function testFindWithHint(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->find(User::class)
            ->hint('foo');

        $expected = 'foo';

        self::assertEquals($expected, $qb->debug('hint'));
        self::assertEquals($expected, $qb->getQuery()->debug('hint'));
    }

    public function testUpsertUpdateQuery(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->upsert(true)
            ->field('username')->set('jwage');

        $expected = [
            '$set' => ['username' => 'jwage'],
        ];
        self::assertEquals($expected, $qb->getNewObj());
        self::assertTrue($qb->debug('upsert'));
    }

    public function testMultipleUpdateQuery(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->updateMany()
            ->field('username')->set('jwage');

        $expected = [
            '$set' => ['username' => 'jwage'],
        ];
        self::assertEquals($expected, $qb->getNewObj());
        self::assertTrue($qb->debug('multiple'));
    }

    public function testComplexUpdateQuery(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('username')
            ->set('jwage')
            ->equals('boo');

        $expected = ['username' => 'boo'];
        self::assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$set' => ['username' => 'jwage'],
        ];
        self::assertEquals($expected, $qb->getNewObj());
    }

    public function testIncUpdateQuery(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('hits')->inc(5)
            ->field('username')->equals('boo');

        $expected = ['username' => 'boo'];
        self::assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$inc' => ['hits' => 5],
        ];
        self::assertEquals($expected, $qb->getNewObj());
    }

    public function testUnsetField(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('hits')->unsetField()
            ->field('username')->equals('boo');

        $expected = ['username' => 'boo'];
        self::assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$unset' => ['hits' => 1],
        ];
        self::assertEquals($expected, $qb->getNewObj());
    }

    public function testSetOnInsert(): void
    {
        $createDate = new DateTime();
        $qb         = $this->getTestQueryBuilder()
            ->updateOne()
            ->upsert()
            ->field('username')->equals('boo')
            ->field('createDate')->setOnInsert($createDate);

        $expected = ['username' => 'boo'];
        self::assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$setOnInsert' => [
                'createDate' => Type::getType('date')->convertToDatabaseValue($createDate),
            ],
        ];
        self::assertEquals($expected, $qb->getNewObj());
    }

    public function testDateRange(): void
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
        self::assertEquals($expected, $qb->getQueryArray());
    }

    public function testQueryIsIterable(): void
    {
        $qb    = $this->getTestQueryBuilder();
        $query = $qb->getQuery();
        self::assertInstanceOf(IteratorAggregate::class, $query);
    }

    public function testDeepClone(): void
    {
        $qb = $this->getTestQueryBuilder();

        $qb->field('username')->equals('jwage');

        self::assertCount(1, $qb->getQueryArray());

        $qb2 = clone $qb;
        $qb2->field('firstName')->equals('Jon');

        self::assertCount(1, $qb->getQueryArray());
    }

    #[DataProvider('provideProxiedExprMethods')]
    public function testProxiedExprMethods(string $method, array $args = []): void
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

        self::assertSame($qb, $qb->$method(...$args));
    }

    public static function provideProxiedExprMethods(): array
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
            'near()' => ['near', [1, 2], null, 5, 10],
            'nearSphere()' => ['nearSphere', [1, 2], null, 5, 10],
            'geoIntersects()' => ['geoIntersects', [self::createGeometry()]],
            'geoWithin()' => ['geoWithin', [self::createGeometry()]],
            'geoWithinBox()' => ['geoWithinBox', [1, 2, 3, 4]],
            'geoWithinCenter()' => ['geoWithinCenter', [1, 2, 3]],
            'geoWithinCenterSphere()' => ['geoWithinCenterSphere', [1, 2, 3]],
            'geoWithinPolygon()' => ['geoWithinPolygon', [[0, 0], [1, 1], [1, 0]]],
            'inc()' => ['inc', [1]],
            'mul()' => ['mul', [1]],
            'unsetField()' => ['unsetField'],
            'setOnInsert()' => ['setOnInsert', [1]],
            'push() with value' => ['push', ['value']],
            'push() with Expr' => ['push', [self::createExpr()]],
            'addToSet() with value' => ['addToSet', ['value']],
            'addToSet() with Expr' => ['addToSet', [self::createExpr()]],
            'popFirst()' => ['popFirst'],
            'popLast()' => ['popLast'],
            'pull()' => ['pull', ['value']],
            'pullAll()' => ['pullAll', [['value1', 'value2']]],
            'addAnd() array' => ['addAnd', [[]]],
            'addAnd() Expr' => ['addAnd', [self::createExpr()]],
            'addOr() array' => ['addOr', [[]]],
            'addOr() Expr' => ['addOr', [self::createExpr()]],
            'addNor() array' => ['addNor', [[]]],
            'addNor() Expr' => ['addNor', [self::createExpr()]],
            'elemMatch() array' => ['elemMatch', [[]]],
            'elemMatch() Expr' => ['elemMatch', [self::createExpr()]],
            'not()' => ['not', [self::createExpr()]],
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

    public function providePoint(): array
    {
        $coordinates = [0, 0];
        $json        = ['type' => 'Point', 'coordinates' => $coordinates];

        return [
            'legacy array' => [$coordinates, $coordinates, false],
            'GeoJSON array' => [$json, $json, true],
            'GeoJSON object' => [new Point($coordinates), $json, true],
        ];
    }

    /** @param string[] $args */
    #[DataProvider('provideSelectProjections')]
    public function testSelect(array $args, array $expected): void
    {
        $qb = $this->getTestQueryBuilder();
        $qb->select(...$args);

        self::assertEquals($expected, $qb->debug('select'));
    }

    public static function provideSelectProjections(): array
    {
        return self::provideProjections(true);
    }

    /** @param string[] $args */
    #[DataProvider('provideExcludeProjections')]
    public function testExclude(array $args, array $expected): void
    {
        $qb = $this->getTestQueryBuilder();
        $qb->exclude(...$args);

        self::assertEquals($expected, $qb->debug('select'));
    }

    public static function provideExcludeProjections(): array
    {
        return self::provideProjections(false);
    }

    /**
     * Provide arguments for select() and exclude() tests.
     *
     * @param bool $include Whether the field should be included or excluded
     */
    private static function provideProjections(bool $include): array
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

    public function testSelectSliceWithCount(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->selectSlice('tags', 10);

        $expected = ['tags' => ['$slice' => 10]];

        self::assertEquals($expected, $qb->debug('select'));
    }

    public function testSelectSliceWithSkipAndLimit(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->selectSlice('tags', -5, 5);

        $expected = ['tags' => ['$slice' => [-5, 5]]];

        self::assertEquals($expected, $qb->debug('select'));
    }

    public function testSelectElemMatchWithArray(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->selectElemMatch('addresses', ['state' => 'ny']);

        $expected = ['addresses' => ['$elemMatch' => ['state' => 'ny']]];

        self::assertEquals($expected, $qb->debug('select'));
    }

    public function testSelectElemMatchWithExpr(): void
    {
        $qb = $this->getTestQueryBuilder();
        $qb->selectElemMatch('addresses', $qb->expr()->field('state')->equals('ny'));

        $expected = ['addresses' => ['$elemMatch' => ['state' => 'ny']]];

        self::assertEquals($expected, $qb->debug('select'));
    }

    public function testSelectMeta(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->selectMeta('score', 'textScore');

        $expected = ['score' => ['$meta' => 'textScore']];

        self::assertEquals($expected, $qb->debug('select'));
    }

    public function testSetReadPreference(): void
    {
        $qb = $this->getTestQueryBuilder();
        $qb->setReadPreference(new ReadPreference('secondary', [['dc' => 'east']]));

        $readPreference = $qb->debug('readPreference');
        self::assertInstanceOf(ReadPreference::class, $readPreference);
        self::assertEquals(ReadPreference::SECONDARY, $readPreference->getModeString());
        self::assertEquals([['dc' => 'east']], $readPreference->getTagSets());
    }

    public function testSortWithFieldNameAndDefaultOrder(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->sort('foo');

        self::assertEquals(['foo' => 1], $qb->debug('sort'));
    }

    /** @param string|int $order */
    #[DataProvider('provideSortOrders')]
    public function testSortWithFieldNameAndOrder($order, int $expectedOrder): void
    {
        $qb = $this->getTestQueryBuilder()
            ->sort('foo', $order);

        self::assertEquals(['foo' => $expectedOrder], $qb->debug('sort'));
    }

    public static function provideSortOrders(): array
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

    public function testSortWithArrayOfFieldNameAndOrderPairs(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->sort(['foo' => 1, 'bar' => -1]);

        self::assertEquals(['foo' => 1, 'bar' => -1], $qb->debug('sort'));
    }

    public function testSortMetaDoesProjectMissingField(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->select('score')
            ->sortMeta('score', 'textScore');

        /* This will likely yield a server error, but sortMeta() should only set
         * the projection if it doesn't already exist.
         */
        self::assertEquals(['score' => 1], $qb->debug('select'));
        self::assertEquals(['score' => ['$meta' => 'textScore']], $qb->debug('sort'));
    }

    public function testSortMetaDoesNotProjectExistingField(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->sortMeta('score', 'textScore');

        self::assertEquals(['score' => ['$meta' => 'textScore']], $qb->debug('select'));
        self::assertEquals(['score' => ['$meta' => 'textScore']], $qb->debug('sort'));
    }

    #[DataProvider('provideCurrentDateOptions')]
    public function testCurrentDateUpdateQuery(string $type): void
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('lastUpdated')->currentDate($type)
            ->field('username')->equals('boo');

        $expected = ['username' => 'boo'];
        self::assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$currentDate' => [
                'lastUpdated' => ['$type' => $type],
            ],
        ];
        self::assertEquals($expected, $qb->getNewObj());
    }

    public static function provideCurrentDateOptions(): array
    {
        return [
            ['date'],
            ['timestamp'],
        ];
    }

    public function testCurrentDateInvalidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getTestQueryBuilder()
            ->updateOne()
            ->field('lastUpdated')->currentDate('notADate');
    }

    public function testBitAndUpdateQuery(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('flags')->bitAnd(15)
            ->field('username')->equals('boo');

        $expected = ['username' => 'boo'];
        self::assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$bit' => [
                'flags' => ['and' => 15],
            ],
        ];
        self::assertEquals($expected, $qb->getNewObj());
    }

    public function testBitOrUpdateQuery(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('flags')->bitOr(15)
            ->field('username')->equals('boo');

        $expected = ['username' => 'boo'];
        self::assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$bit' => [
                'flags' => ['or' => 15],
            ],
        ];
        self::assertEquals($expected, $qb->getNewObj());
    }

    public function testBitXorUpdateQuery(): void
    {
        $qb = $this->getTestQueryBuilder()
            ->updateOne()
            ->field('flags')->bitXor(15)
            ->field('username')->equals('boo');

        $expected = ['username' => 'boo'];
        self::assertEquals($expected, $qb->getQueryArray());

        $expected = [
            '$bit' => [
                'flags' => ['xor' => 15],
            ],
        ];
        self::assertEquals($expected, $qb->getNewObj());
    }

    public function testNonRewindable(): void
    {
        $query = $this->getTestQueryBuilder()
            ->setRewindable(false)
            ->getQuery();

        self::assertInstanceOf(UnrewindableIterator::class, $query->execute());
    }

    private function getTestQueryBuilder(): Builder
    {
        return new Builder($this->dm, User::class);
    }

    /** @return MockObject&Expr */
    private function getMockExpr()
    {
        return $this->createMock(Expr::class);
    }

    private static function createExpr(): Expr
    {
        return new Expr(static::createTestDocumentManager());
    }

    private static function createGeometry(): Geometry
    {
        return new class extends Geometry {
        };
    }
}

#[ODM\Document]
#[ODM\InheritanceType('SINGLE_COLLECTION')]
#[ODM\DiscriminatorField('type')]
#[ODM\DiscriminatorMap(['ca' => ChildA::class, 'cb' => ChildB::class, 'cc' => ChildC::class])]
class ParentClass
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
}

#[ODM\Document]
class ChildA extends ParentClass
{
    /** @var Feature|null */
    #[ODM\ReferenceOne(targetDocument: Feature::class)]
    public $featureFull;

    /** @var Collection<int, Feature> */
    #[ODM\ReferenceMany(targetDocument: Feature::class)]
    public $featureFullMany;

    /** @var Feature|null */
    #[ODM\ReferenceOne(targetDocument: Feature::class)]
    public $conflict;

    /** @var Collection<int, Feature> */
    #[ODM\ReferenceMany(targetDocument: Feature::class)]
    public $conflictMany;
}

#[ODM\Document]
class ChildB extends ParentClass
{
    /** @var Feature|null */
    #[ODM\ReferenceOne(targetDocument: Feature::class, storeAs: 'id')]
    public $featureSimple;

    /** @var Collection<int, Feature> */
    #[ODM\ReferenceMany(targetDocument: Feature::class, storeAs: 'id')]
    public $featureSimpleMany;

    /** @var Feature|null */
    #[ODM\ReferenceOne(targetDocument: Feature::class, storeAs: 'id')]
    public $conflict;

    /** @var Collection<int, Feature> */
    #[ODM\ReferenceMany(targetDocument: Feature::class, storeAs: 'id')]
    public $conflictMany;
}

#[ODM\Document]
class ChildC extends ParentClass
{
    /** @var object|null */
    #[ODM\ReferenceOne(storeAs: 'dbRef')]
    public $featurePartial;

    /** @var Collection<int, object> */
    #[ODM\ReferenceMany(storeAs: 'dbRef')]
    public $featurePartialMany;
}
