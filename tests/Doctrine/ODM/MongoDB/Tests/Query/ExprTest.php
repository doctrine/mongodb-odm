<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Query;

use BadMethodCallException;
use Doctrine\ODM\MongoDB\Query\Expr;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Profile;
use Documents\User;
use GeoJson\Geometry\Point;
use GeoJson\Geometry\Polygon;
use MongoDB\BSON\ObjectId;
use PHPUnit\Framework\MockObject\MockObject;

class ExprTest extends BaseTest
{
    public function testSelectIsPrepared(): void
    {
        $qb    = $this->dm->createQueryBuilder(User::class)
            ->select('id');
        $query = $qb->getQuery();

        self::assertEquals(['_id' => 1], $query->debug('select'));
    }

    public function testInIsPrepared(): void
    {
        $ids = ['4f28aa84acee41388900000a'];

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('groups.id')->in($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        self::assertInstanceOf(ObjectId::class, $debug['groups.$id']['$in'][0]);
        self::assertEquals($ids[0], (string) $debug['groups.$id']['$in'][0]);
    }

    public function testAllIsPrepared(): void
    {
        $ids = ['4f28aa84acee41388900000a'];

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('groups.id')->all($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        self::assertInstanceOf(ObjectId::class, $debug['groups.$id']['$all'][0]);
        self::assertEquals($ids[0], (string) $debug['groups.$id']['$all'][0]);
    }

    public function testNotEqualIsPrepared(): void
    {
        $id = '4f28aa84acee41388900000a';

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('groups.id')->notEqual($id)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        self::assertInstanceOf(ObjectId::class, $debug['groups.$id']['$ne']);
        self::assertEquals($id, (string) $debug['groups.$id']['$ne']);
    }

    public function testNotInIsPrepared(): void
    {
        $ids = ['4f28aa84acee41388900000a'];

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('groups.id')->notIn($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        self::assertInstanceOf(ObjectId::class, $debug['groups.$id']['$nin'][0]);
        self::assertEquals($ids[0], (string) $debug['groups.$id']['$nin'][0]);
    }

    public function testAndIsPrepared(): void
    {
        $ids = ['4f28aa84acee41388900000a'];

        $qb = $this->dm->createQueryBuilder(User::class);
        $qb
            ->addAnd($qb->expr()->field('groups.id')->in($ids))
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        self::assertInstanceOf(ObjectId::class, $debug['$and'][0]['groups.$id']['$in'][0]);
        self::assertEquals($ids[0], (string) $debug['$and'][0]['groups.$id']['$in'][0]);
    }

    public function testOrIsPrepared(): void
    {
        $ids = ['4f28aa84acee41388900000a'];

        $qb = $this->dm->createQueryBuilder(User::class);
        $qb
            ->addOr($qb->expr()->field('groups.id')->in($ids))
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        self::assertInstanceOf(ObjectId::class, $debug['$or'][0]['groups.$id']['$in'][0]);
        self::assertEquals($ids[0], (string) $debug['$or'][0]['groups.$id']['$in'][0]);
    }

    public function testMultipleQueryOperatorsArePrepared(): void
    {
        $all = ['4f28aa84acee41388900000a'];
        $in  = ['4f28aa84acee41388900000b'];
        $ne  = '4f28aa84acee41388900000c';
        $nin = ['4f28aa84acee41388900000d'];

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('groups.id')->all($all)
            ->field('groups.id')->in($in)
            ->field('groups.id')->notEqual($ne)
            ->field('groups.id')->notIn($nin)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        self::assertInstanceOf(ObjectId::class, $debug['groups.$id']['$all'][0]);
        self::assertEquals($all[0], (string) $debug['groups.$id']['$all'][0]);
        self::assertInstanceOf(ObjectId::class, $debug['groups.$id']['$in'][0]);
        self::assertEquals($in[0], (string) $debug['groups.$id']['$in'][0]);
        self::assertInstanceOf(ObjectId::class, $debug['groups.$id']['$ne']);
        self::assertEquals($ne, (string) $debug['groups.$id']['$ne']);
        self::assertInstanceOf(ObjectId::class, $debug['groups.$id']['$nin'][0]);
        self::assertEquals($nin[0], (string) $debug['groups.$id']['$nin'][0]);
    }

    public function testPrepareNestedDocuments(): void
    {
        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('address.subAddress.subAddress.subAddress.test')->equals('test');
        $query = $qb->getQuery();
        $debug = $query->debug('query');
        self::assertEquals(['address.subAddress.subAddress.subAddress.testFieldName' => 'test'], $debug);
    }

    public function testPreparePositionalOperator(): void
    {
        $qb = $this->dm->createQueryBuilder(User::class)
            ->updateOne()
            ->field('phonenumbers.$.phonenumber')->equals('foo')
            ->field('phonenumbers.$')->set(['phonenumber' => 'bar']);

        self::assertEquals(['phonenumbers.$.phonenumber' => 'foo'], $qb->getQueryArray());
        self::assertEquals(['$set' => ['phonenumbers.$' => ['phonenumber' => 'bar']]], $qb->getNewObj());
    }

    public function testSortIsPrepared(): void
    {
        $qb    = $this->dm->createQueryBuilder(User::class)
            ->sort('id', 'desc');
        $query = $qb->getQuery();
        $query = $query->getQuery();
        self::assertEquals(['_id' => -1], $query['sort']);

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->sort('address.subAddress.subAddress.subAddress.test', 'asc');
        $query = $qb->getQuery();
        $query = $query->getQuery();
        self::assertEquals(['address.subAddress.subAddress.subAddress.testFieldName' => 1], $query['sort']);
    }

    public function testNestedWithOperator(): void
    {
        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('address.subAddress.subAddress.subAddress.test')->notIn(['test']);
        $query = $qb->getQuery();
        $query = $query->getQuery();
        self::assertEquals(['address.subAddress.subAddress.subAddress.testFieldName' => ['$nin' => ['test']]], $query['query']);
    }

    public function testNewObjectIsPrepared(): void
    {
        $qb    = $this->dm->createQueryBuilder(User::class)
            ->updateOne()
            ->field('address.subAddress.subAddress.subAddress.test')->popFirst();
        $query = $qb->getQuery();
        $query = $query->getQuery();
        self::assertEquals(['$pop' => ['address.subAddress.subAddress.subAddress.testFieldName' => -1]], $query['newObj']);
    }

    public function testReferencesUsesMinimalKeys(): void
    {
        $profile = new Profile();
        $profile->setProfileId(new ObjectId());
        $this->dm->persist($profile);

        $expr = $this->createExpr();
        $expr->field('profile')->references($profile);

        self::assertEquals(
            ['profile.$id' => $profile->getProfileId()],
            $expr->getQuery(),
            '->references() uses just $id if a targetDocument is set',
        );
    }

    public function testReferencesUsesAllKeys(): void
    {
        $profile = new Profile();
        $profile->setProfileId(new ObjectId());
        $this->dm->persist($profile);

        $expr = $this->createExpr();
        $expr->field('referenceToAnything')->references($profile);

        self::assertEquals(
            [
                'referenceToAnything.$id' => $profile->getProfileId(),
                'referenceToAnything.$db' => 'doctrine_odm_tests',
                'referenceToAnything.$ref' => 'Profile',
            ],
            $expr->getQuery(),
            '->references() uses all keys if no targetDocument is set',
        );
    }

    public function testReferencesUsesSomeKeys(): void
    {
        $profile = new Profile();
        $profile->setProfileId(new ObjectId());
        $this->dm->persist($profile);

        $expr = $this->createExpr();
        $expr->field('referenceToAnythingWithoutDb')->references($profile);

        self::assertEquals(
            [
                'referenceToAnythingWithoutDb.$id' => $profile->getProfileId(),
                'referenceToAnythingWithoutDb.$ref' => 'Profile',
            ],
            $expr->getQuery(),
            '->references() uses some keys if storeAs=dbRef is set',
        );
    }

    public function testAddToSetWithValue(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->field('a')->addToSet(1));
        self::assertEquals(['$addToSet' => ['a' => 1]], $expr->getNewObj());
    }

    public function testAddToSetWithExpression(): void
    {
        $expr     = $this->createExpr();
        $eachExpr = $this->createExpr();
        $eachExpr->each([1, 2]);

        self::assertSame($expr, $expr->field('a')->addToSet($eachExpr));
        self::assertEquals(['$addToSet' => ['a' => ['$each' => [1, 2]]]], $expr->getNewObj());
    }

    public function testLanguageWithText(): void
    {
        $expr = $this->createExpr();
        $expr->text('foo');

        self::assertSame($expr, $expr->language('en'));
        self::assertEquals(['$text' => ['$search' => 'foo', '$language' => 'en']], $expr->getQuery());
    }

    public function testLanguageRequiresTextOperator(): void
    {
        $expr = $this->createExpr();
        $this->expectException(BadMethodCallException::class);
        $expr->language('en');
    }

    public function testCaseSensitiveWithText(): void
    {
        $expr = $this->createExpr();
        $expr->text('foo');

        self::assertSame($expr, $expr->caseSensitive(true));
        self::assertEquals(['$text' => ['$search' => 'foo', '$caseSensitive' => true]], $expr->getQuery());
    }

    public function testCaseSensitiveFalseRemovesOption(): void
    {
        $expr = $this->createExpr();
        $expr->text('foo');

        $expr->caseSensitive(true);
        $expr->caseSensitive(false);
        self::assertEquals(['$text' => ['$search' => 'foo']], $expr->getQuery());
    }

    public function testCaseSensitiveRequiresTextOperator(): void
    {
        $expr = $this->createExpr();
        $this->expectException(BadMethodCallException::class);
        $expr->caseSensitive(false);
    }

    public function testDiacriticSensitiveWithText(): void
    {
        $expr = $this->createExpr();
        $expr->text('foo');

        self::assertSame($expr, $expr->diacriticSensitive(true));
        self::assertEquals(['$text' => ['$search' => 'foo', '$diacriticSensitive' => true]], $expr->getQuery());
    }

    public function testDiacriticSensitiveFalseRemovesOption(): void
    {
        $expr = $this->createExpr();
        $expr->text('foo');

        $expr->diacriticSensitive(true);
        $expr->diacriticSensitive(false);
        self::assertEquals(['$text' => ['$search' => 'foo']], $expr->getQuery());
    }

    public function testDiacriticSensitiveRequiresTextOperator(): void
    {
        $expr = $this->createExpr();
        $this->expectException(BadMethodCallException::class);
        $expr->diacriticSensitive(false);
    }

    public function testOperatorWithCurrentField(): void
    {
        $expr = $this->createExpr();
        $expr->field('field');

        self::assertSame($expr, $expr->operator('$op', 'value'));
        self::assertEquals(['field' => ['$op' => 'value']], $expr->getQuery());
    }

    public function testOperatorWithCurrentFieldWrapsEqualityCriteria(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->field('a')->equals(1));
        self::assertSame($expr, $expr->field('a')->lt(2));
        self::assertSame($expr, $expr->field('b')->equals(null));
        self::assertSame($expr, $expr->field('b')->lt(2));
        self::assertSame($expr, $expr->field('c')->equals([]));
        self::assertSame($expr, $expr->field('c')->lt(2));
        self::assertSame($expr, $expr->field('d')->equals(['x' => 1]));
        self::assertSame($expr, $expr->field('d')->lt(2));

        $expectedQuery = [
            'a' => ['$in' => [1], '$lt' => 2],
            'b' => ['$in' => [null], '$lt' => 2],
            // Equality match on empty array cannot be distinguished from no criteria and will be overridden
            'c' => ['$lt' => 2],
            'd' => ['$in' => [['x' => 1]], '$lt' => 2],
        ];

        self::assertEquals($expectedQuery, $expr->getQuery());
    }

    public function testOperatorWithoutCurrentField(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->operator('$op', 'value'));
        self::assertEquals(['$op' => 'value'], $expr->getQuery());
    }

    public function testOperatorWithoutCurrentFieldWrapsEqualityCriteria(): void
    {
        $expr = $this->createExpr();
        self::assertSame($expr, $expr->equals(1));
        self::assertSame($expr, $expr->lt(2));
        self::assertEquals(['$in' => [1], '$lt' => 2], $expr->getQuery());

        $expr = $this->createExpr();
        self::assertSame($expr, $expr->equals(null));
        self::assertSame($expr, $expr->lt(2));
        self::assertEquals(['$in' => [null], '$lt' => 2], $expr->getQuery());

        $expr = $this->createExpr();
        self::assertSame($expr, $expr->equals([]));
        self::assertSame($expr, $expr->lt(2));
        // Equality match on empty array cannot be distinguished from no criteria and will be overridden
        self::assertEquals(['$lt' => 2], $expr->getQuery());

        $expr = $this->createExpr();
        self::assertSame($expr, $expr->equals(['x' => 1]));
        self::assertSame($expr, $expr->lt(2));
        self::assertEquals(['$in' => [['x' => 1]], '$lt' => 2], $expr->getQuery());
    }

    public function provideGeoJsonPoint(): array
    {
        $json     = ['type' => 'Point', 'coordinates' => [1, 2]];
        $expected = ['$geometry' => $json];

        return [
            'array' => [$json, $expected],
            'object' => [$this->getMockPoint($json), $expected],
        ];
    }

    /**
     * @param Point|array<string, mixed> $point
     * @param array<string, mixed>       $expected
     *
     * @dataProvider provideGeoJsonPoint
     */
    public function testNearWithGeoJsonPoint($point, array $expected): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->near($point));
        self::assertEquals(['$near' => $expected], $expr->getQuery());
    }

    public function testNearWithLegacyCoordinates(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->near(1, 2));
        self::assertEquals(['$near' => [1, 2]], $expr->getQuery());
    }

    /**
     * @param Point|array<string, mixed> $point
     * @param array<string, mixed>       $expected
     *
     * @dataProvider provideGeoJsonPoint
     */
    public function testNearSphereWithGeoJsonPoint($point, array $expected): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->nearSphere($point));
        self::assertEquals(['$nearSphere' => $expected], $expr->getQuery());
    }

    public function testNearSphereWithLegacyCoordinates(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->nearSphere(1, 2));
        self::assertEquals(['$nearSphere' => [1, 2]], $expr->getQuery());
    }

    public function testPullWithValue(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->field('a')->pull(1));
        self::assertEquals(['$pull' => ['a' => 1]], $expr->getNewObj());
    }

    public function testPullWithExpression(): void
    {
        $expr       = $this->createExpr();
        $nestedExpr = $this->createExpr();
        $nestedExpr->gt(3);

        self::assertSame($expr, $expr->field('a')->pull($nestedExpr));
        self::assertEquals(['$pull' => ['a' => ['$gt' => 3]]], $expr->getNewObj());
    }

    public function testPushWithValue(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->field('a')->push(1));
        self::assertEquals(['$push' => ['a' => 1]], $expr->getNewObj());
    }

    public function testPushWithExpression(): void
    {
        $expr      = $this->createExpr();
        $innerExpr = $this->createExpr();
        $innerExpr
            ->each([['x' => 1], ['x' => 2]])
            ->slice(-2)
            ->sort('x', 1);

        $expectedNewObj = [
            '$push' => [
                'a' => [
                    '$each' => [['x' => 1], ['x' => 2]],
                    '$slice' => -2,
                    '$sort' => ['x' => 1],
                ],
            ],
        ];

        self::assertSame($expr, $expr->field('a')->push($innerExpr));
        self::assertEquals($expectedNewObj, $expr->getNewObj());
    }

    public function testPushWithExpressionShouldEnsureEachOperatorAppearsFirst(): void
    {
        $expr      = $this->createExpr();
        $innerExpr = $this->createExpr();
        $innerExpr
            ->sort('x', 1)
            ->slice(-2)
            ->each([['x' => 1], ['x' => 2]]);

        $expectedNewObj = [
            '$push' => [
                'a' => [
                    '$each' => [['x' => 1], ['x' => 2]],
                    '$sort' => ['x' => 1],
                    '$slice' => -2,
                ],
            ],
        ];

        self::assertSame($expr, $expr->field('a')->push($innerExpr));
        self::assertSame($expectedNewObj, $expr->getNewObj());
    }

    public function testPushWithPosition(): void
    {
        $expr      = $this->createExpr();
        $innerExpr = $this->createExpr();
        $innerExpr
            ->each([20, 30])
            ->position(0);

        $expectedNewObj = [
            '$push' => [
                'a' => [
                    '$each' => [20, 30],
                    '$position' => 0,
                ],
            ],
        ];

        self::assertSame($expr, $expr->field('a')->push($innerExpr));
        self::assertEquals($expectedNewObj, $expr->getNewObj());
    }

    /**
     * @param Polygon|array<string, array<string, mixed>> $geometry
     * @param array<string, mixed>                        $expected
     *
     * @dataProvider provideGeoJsonPolygon
     */
    public function testGeoIntersects($geometry, array $expected): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->geoIntersects($geometry));
        self::assertEquals(['$geoIntersects' => $expected], $expr->getQuery());
    }

    public function provideGeoJsonPolygon(): array
    {
        $json = [
            'type' => 'Polygon',
            'coordinates' => [[[0, 0], [1, 1], [1, 0], [0, 0]]],
        ];

        $expected = ['$geometry' => $json];

        return [
            'array' => [$json, $expected],
            'object' => [$this->getMockPolygon($json), $expected],
        ];
    }

    /**
     * @param Polygon|array<string, array<string, mixed>> $geometry
     * @param array<string, mixed>                        $expected
     *
     * @dataProvider provideGeoJsonPolygon
     */
    public function testGeoWithin($geometry, array $expected): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->geoWithin($geometry));
        self::assertEquals(['$geoWithin' => $expected], $expr->getQuery());
    }

    public function testGeoWithinBox(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->geoWithinBox(1, 2, 3, 4));
        self::assertEquals(['$geoWithin' => ['$box' => [[1, 2], [3, 4]]]], $expr->getQuery());
    }

    public function testGeoWithinCenter(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->geoWithinCenter(1, 2, 3));
        self::assertEquals(['$geoWithin' => ['$center' => [[1, 2], 3]]], $expr->getQuery());
    }

    public function testGeoWithinCenterSphere(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->geoWithinCenterSphere(1, 2, 3));
        self::assertEquals(['$geoWithin' => ['$centerSphere' => [[1, 2], 3]]], $expr->getQuery());
    }

    public function testGeoWithinPolygon(): void
    {
        $expr          = $this->createExpr();
        $expectedQuery = ['$geoWithin' => ['$polygon' => [[0, 0], [1, 1], [1, 0]]]];

        self::assertSame($expr, $expr->geoWithinPolygon([0, 0], [1, 1], [1, 0]));
        self::assertEquals($expectedQuery, $expr->getQuery());
    }

    public function testSetWithAtomic(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->field('a')->set(1, true));
        self::assertEquals(['$set' => ['a' => 1]], $expr->getNewObj());
    }

    public function testSetWithoutAtomicWithTopLevelField(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->field('a')->set(1, false));
        self::assertEquals(['a' => 1], $expr->getNewObj());
    }

    public function testSetWithoutAtomicWithNestedField(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->field('a.b.c')->set(1, false));
        self::assertEquals(['a' => ['b' => ['c' => 1]]], $expr->getNewObj());
    }

    public function testText(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->text('foo'));
        self::assertEquals(['$text' => ['$search' => 'foo']], $expr->getQuery());
        self::assertNull($expr->getCurrentField());
    }

    public function testWhere(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->where('javascript'));
        self::assertEquals(['$where' => 'javascript'], $expr->getQuery());
        self::assertNull($expr->getCurrentField());
    }

    public function testIn(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->in(['value1', 'value2']));
        self::assertEquals(['$in' => ['value1', 'value2']], $expr->getQuery());
    }

    public function testInWillStripKeysToYieldBsonArray(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->in([1 => 'value1', 'some' => 'value2']));
        self::assertEquals(['$in' => ['value1', 'value2']], $expr->getQuery());
    }

    public function testNotIn(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->notIn(['value1', 'value2']));
        self::assertEquals(['$nin' => ['value1', 'value2']], $expr->getQuery());
    }

    public function testNotInWillStripKeysToYieldBsonArray(): void
    {
        $expr = $this->createExpr();

        self::assertSame($expr, $expr->notIn([1 => 'value1', 'some' => 'value2']));
        self::assertEquals(['$nin' => ['value1', 'value2']], $expr->getQuery());
    }

    private function createExpr(): Expr
    {
        $expr = new Expr($this->dm);
        $expr->setClassMetadata($this->dm->getClassMetadata(User::class));

        return $expr;
    }

    /**
     * @param array<string, mixed> $json
     *
     * @return MockObject&Point
     */
    private function getMockPoint(array $json)
    {
        $point = $this->createMock(Point::class);

        $point->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn($json);

        return $point;
    }

    /**
     * @param array<string, mixed> $json
     *
     * @return MockObject&Polygon
     */
    private function getMockPolygon(array $json)
    {
        $point = $this->createMock(Polygon::class);

        $point->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn($json);

        return $point;
    }
}
