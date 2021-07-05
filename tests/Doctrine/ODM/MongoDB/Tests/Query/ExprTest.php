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

class ExprTest extends BaseTest
{
    public function testSelectIsPrepared(): void
    {
        $qb    = $this->dm->createQueryBuilder(User::class)
            ->select('id');
        $query = $qb->getQuery();

        $this->assertEquals(['_id' => 1], $query->debug('select'));
    }

    public function testInIsPrepared(): void
    {
        $ids = ['4f28aa84acee41388900000a'];

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('groups.id')->in($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf(ObjectId::class, $debug['groups.$id']['$in'][0]);
        $this->assertEquals($ids[0], (string) $debug['groups.$id']['$in'][0]);
    }

    public function testAllIsPrepared(): void
    {
        $ids = ['4f28aa84acee41388900000a'];

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('groups.id')->all($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf(ObjectId::class, $debug['groups.$id']['$all'][0]);
        $this->assertEquals($ids[0], (string) $debug['groups.$id']['$all'][0]);
    }

    public function testNotEqualIsPrepared(): void
    {
        $id = '4f28aa84acee41388900000a';

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('groups.id')->notEqual($id)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf(ObjectId::class, $debug['groups.$id']['$ne']);
        $this->assertEquals($id, (string) $debug['groups.$id']['$ne']);
    }

    public function testNotInIsPrepared(): void
    {
        $ids = ['4f28aa84acee41388900000a'];

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('groups.id')->notIn($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf(ObjectId::class, $debug['groups.$id']['$nin'][0]);
        $this->assertEquals($ids[0], (string) $debug['groups.$id']['$nin'][0]);
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

        $this->assertInstanceOf(ObjectId::class, $debug['$and'][0]['groups.$id']['$in'][0]);
        $this->assertEquals($ids[0], (string) $debug['$and'][0]['groups.$id']['$in'][0]);
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

        $this->assertInstanceOf(ObjectId::class, $debug['$or'][0]['groups.$id']['$in'][0]);
        $this->assertEquals($ids[0], (string) $debug['$or'][0]['groups.$id']['$in'][0]);
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

        $this->assertInstanceOf(ObjectId::class, $debug['groups.$id']['$all'][0]);
        $this->assertEquals($all[0], (string) $debug['groups.$id']['$all'][0]);
        $this->assertInstanceOf(ObjectId::class, $debug['groups.$id']['$in'][0]);
        $this->assertEquals($in[0], (string) $debug['groups.$id']['$in'][0]);
        $this->assertInstanceOf(ObjectId::class, $debug['groups.$id']['$ne']);
        $this->assertEquals($ne, (string) $debug['groups.$id']['$ne']);
        $this->assertInstanceOf(ObjectId::class, $debug['groups.$id']['$nin'][0]);
        $this->assertEquals($nin[0], (string) $debug['groups.$id']['$nin'][0]);
    }

    public function testPrepareNestedDocuments(): void
    {
        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('address.subAddress.subAddress.subAddress.test')->equals('test');
        $query = $qb->getQuery();
        $debug = $query->debug('query');
        $this->assertEquals(['address.subAddress.subAddress.subAddress.testFieldName' => 'test'], $debug);
    }

    public function testPreparePositionalOperator(): void
    {
        $qb = $this->dm->createQueryBuilder(User::class)
            ->updateOne()
            ->field('phonenumbers.$.phonenumber')->equals('foo')
            ->field('phonenumbers.$')->set(['phonenumber' => 'bar']);

        $this->assertEquals(['phonenumbers.$.phonenumber' => 'foo'], $qb->getQueryArray());
        $this->assertEquals(['$set' => ['phonenumbers.$' => ['phonenumber' => 'bar']]], $qb->getNewObj());
    }

    public function testSortIsPrepared(): void
    {
        $qb    = $this->dm->createQueryBuilder(User::class)
            ->sort('id', 'desc');
        $query = $qb->getQuery();
        $query = $query->getQuery();
        $this->assertEquals(['_id' => -1], $query['sort']);

        $qb    = $this->dm->createQueryBuilder(User::class)
            ->sort('address.subAddress.subAddress.subAddress.test', 'asc');
        $query = $qb->getQuery();
        $query = $query->getQuery();
        $this->assertEquals(['address.subAddress.subAddress.subAddress.testFieldName' => 1], $query['sort']);
    }

    public function testNestedWithOperator(): void
    {
        $qb    = $this->dm->createQueryBuilder(User::class)
            ->field('address.subAddress.subAddress.subAddress.test')->notIn(['test']);
        $query = $qb->getQuery();
        $query = $query->getQuery();
        $this->assertEquals(['address.subAddress.subAddress.subAddress.testFieldName' => ['$nin' => ['test']]], $query['query']);
    }

    public function testNewObjectIsPrepared(): void
    {
        $qb    = $this->dm->createQueryBuilder(User::class)
            ->updateOne()
            ->field('address.subAddress.subAddress.subAddress.test')->popFirst();
        $query = $qb->getQuery();
        $query = $query->getQuery();
        $this->assertEquals(['$pop' => ['address.subAddress.subAddress.subAddress.testFieldName' => -1]], $query['newObj']);
    }

    public function testReferencesUsesMinimalKeys(): void
    {
        $profile = new Profile();
        $profile->setProfileId(new ObjectId());
        $this->dm->persist($profile);

        $expr = $this->createExpr();
        $expr->field('profile')->references($profile);

        $this->assertEquals(
            ['profile.$id' => $profile->getProfileId()],
            $expr->getQuery(),
            '->references() uses just $id if a targetDocument is set'
        );
    }

    public function testReferencesUsesAllKeys(): void
    {
        $profile = new Profile();
        $profile->setProfileId(new ObjectId());
        $this->dm->persist($profile);

        $expr = $this->createExpr();
        $expr->field('referenceToAnything')->references($profile);

        $this->assertEquals(
            [
                'referenceToAnything.$id' => $profile->getProfileId(),
                'referenceToAnything.$db' => 'doctrine_odm_tests',
                'referenceToAnything.$ref' => 'Profile',
            ],
            $expr->getQuery(),
            '->references() uses all keys if no targetDocument is set'
        );
    }

    public function testReferencesUsesSomeKeys(): void
    {
        $profile = new Profile();
        $profile->setProfileId(new ObjectId());
        $this->dm->persist($profile);

        $expr = $this->createExpr();
        $expr->field('referenceToAnythingWithoutDb')->references($profile);

        $this->assertEquals(
            [
                'referenceToAnythingWithoutDb.$id' => $profile->getProfileId(),
                'referenceToAnythingWithoutDb.$ref' => 'Profile',
            ],
            $expr->getQuery(),
            '->references() uses some keys if storeAs=dbRef is set'
        );
    }

    public function testAddToSetWithValue(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a')->addToSet(1));
        $this->assertEquals(['$addToSet' => ['a' => 1]], $expr->getNewObj());
    }

    public function testAddToSetWithExpression(): void
    {
        $expr     = $this->createExpr();
        $eachExpr = $this->createExpr();
        $eachExpr->each([1, 2]);

        $this->assertSame($expr, $expr->field('a')->addToSet($eachExpr));
        $this->assertEquals(['$addToSet' => ['a' => ['$each' => [1, 2]]]], $expr->getNewObj());
    }

    public function testLanguageWithText(): void
    {
        $expr = $this->createExpr();
        $expr->text('foo');

        $this->assertSame($expr, $expr->language('en'));
        $this->assertEquals(['$text' => ['$search' => 'foo', '$language' => 'en']], $expr->getQuery());
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

        $this->assertSame($expr, $expr->caseSensitive(true));
        $this->assertEquals(['$text' => ['$search' => 'foo', '$caseSensitive' => true]], $expr->getQuery());
    }

    public function testCaseSensitiveFalseRemovesOption(): void
    {
        $expr = $this->createExpr();
        $expr->text('foo');

        $expr->caseSensitive(true);
        $expr->caseSensitive(false);
        $this->assertEquals(['$text' => ['$search' => 'foo']], $expr->getQuery());
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

        $this->assertSame($expr, $expr->diacriticSensitive(true));
        $this->assertEquals(['$text' => ['$search' => 'foo', '$diacriticSensitive' => true]], $expr->getQuery());
    }

    public function testDiacriticSensitiveFalseRemovesOption(): void
    {
        $expr = $this->createExpr();
        $expr->text('foo');

        $expr->diacriticSensitive(true);
        $expr->diacriticSensitive(false);
        $this->assertEquals(['$text' => ['$search' => 'foo']], $expr->getQuery());
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

        $this->assertSame($expr, $expr->operator('$op', 'value'));
        $this->assertEquals(['field' => ['$op' => 'value']], $expr->getQuery());
    }

    public function testOperatorWithCurrentFieldWrapsEqualityCriteria(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a')->equals(1));
        $this->assertSame($expr, $expr->field('a')->lt(2));
        $this->assertSame($expr, $expr->field('b')->equals(null));
        $this->assertSame($expr, $expr->field('b')->lt(2));
        $this->assertSame($expr, $expr->field('c')->equals([]));
        $this->assertSame($expr, $expr->field('c')->lt(2));
        $this->assertSame($expr, $expr->field('d')->equals(['x' => 1]));
        $this->assertSame($expr, $expr->field('d')->lt(2));

        $expectedQuery = [
            'a' => ['$in' => [1], '$lt' => 2],
            'b' => ['$in' => [null], '$lt' => 2],
            // Equality match on empty array cannot be distinguished from no criteria and will be overridden
            'c' => ['$lt' => 2],
            'd' => ['$in' => [['x' => 1]], '$lt' => 2],
        ];

        $this->assertEquals($expectedQuery, $expr->getQuery());
    }

    public function testOperatorWithoutCurrentField(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->operator('$op', 'value'));
        $this->assertEquals(['$op' => 'value'], $expr->getQuery());
    }

    public function testOperatorWithoutCurrentFieldWrapsEqualityCriteria(): void
    {
        $expr = $this->createExpr();
        $this->assertSame($expr, $expr->equals(1));
        $this->assertSame($expr, $expr->lt(2));
        $this->assertEquals(['$in' => [1], '$lt' => 2], $expr->getQuery());

        $expr = $this->createExpr();
        $this->assertSame($expr, $expr->equals(null));
        $this->assertSame($expr, $expr->lt(2));
        $this->assertEquals(['$in' => [null], '$lt' => 2], $expr->getQuery());

        $expr = $this->createExpr();
        $this->assertSame($expr, $expr->equals([]));
        $this->assertSame($expr, $expr->lt(2));
        // Equality match on empty array cannot be distinguished from no criteria and will be overridden
        $this->assertEquals(['$lt' => 2], $expr->getQuery());

        $expr = $this->createExpr();
        $this->assertSame($expr, $expr->equals(['x' => 1]));
        $this->assertSame($expr, $expr->lt(2));
        $this->assertEquals(['$in' => [['x' => 1]], '$lt' => 2], $expr->getQuery());
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
     * @dataProvider provideGeoJsonPoint
     */
    public function testNearWithGeoJsonPoint($point, array $expected): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->near($point));
        $this->assertEquals(['$near' => $expected], $expr->getQuery());
    }

    public function testNearWithLegacyCoordinates(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->near(1, 2));
        $this->assertEquals(['$near' => [1, 2]], $expr->getQuery());
    }

    /**
     * @dataProvider provideGeoJsonPoint
     */
    public function testNearSphereWithGeoJsonPoint($point, array $expected): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->nearSphere($point));
        $this->assertEquals(['$nearSphere' => $expected], $expr->getQuery());
    }

    public function testNearSphereWithLegacyCoordinates(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->nearSphere(1, 2));
        $this->assertEquals(['$nearSphere' => [1, 2]], $expr->getQuery());
    }

    public function testPullWithValue(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a')->pull(1));
        $this->assertEquals(['$pull' => ['a' => 1]], $expr->getNewObj());
    }

    public function testPullWithExpression(): void
    {
        $expr       = $this->createExpr();
        $nestedExpr = $this->createExpr();
        $nestedExpr->gt(3);

        $this->assertSame($expr, $expr->field('a')->pull($nestedExpr));
        $this->assertEquals(['$pull' => ['a' => ['$gt' => 3]]], $expr->getNewObj());
    }

    public function testPushWithValue(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a')->push(1));
        $this->assertEquals(['$push' => ['a' => 1]], $expr->getNewObj());
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

        $this->assertSame($expr, $expr->field('a')->push($innerExpr));
        $this->assertEquals($expectedNewObj, $expr->getNewObj());
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

        $this->assertSame($expr, $expr->field('a')->push($innerExpr));
        $this->assertSame($expectedNewObj, $expr->getNewObj());
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

        $this->assertSame($expr, $expr->field('a')->push($innerExpr));
        $this->assertEquals($expectedNewObj, $expr->getNewObj());
    }

    /**
     * @dataProvider provideGeoJsonPolygon
     */
    public function testGeoIntersects($geometry, array $expected): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->geoIntersects($geometry));
        $this->assertEquals(['$geoIntersects' => $expected], $expr->getQuery());
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
     * @dataProvider provideGeoJsonPolygon
     */
    public function testGeoWithin($geometry, array $expected): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->geoWithin($geometry));
        $this->assertEquals(['$geoWithin' => $expected], $expr->getQuery());
    }

    public function testGeoWithinBox(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->geoWithinBox(1, 2, 3, 4));
        $this->assertEquals(['$geoWithin' => ['$box' => [[1, 2], [3, 4]]]], $expr->getQuery());
    }

    public function testGeoWithinCenter(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->geoWithinCenter(1, 2, 3));
        $this->assertEquals(['$geoWithin' => ['$center' => [[1, 2], 3]]], $expr->getQuery());
    }

    public function testGeoWithinCenterSphere(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->geoWithinCenterSphere(1, 2, 3));
        $this->assertEquals(['$geoWithin' => ['$centerSphere' => [[1, 2], 3]]], $expr->getQuery());
    }

    public function testGeoWithinPolygon(): void
    {
        $expr          = $this->createExpr();
        $expectedQuery = ['$geoWithin' => ['$polygon' => [[0, 0], [1, 1], [1, 0]]]];

        $this->assertSame($expr, $expr->geoWithinPolygon([0, 0], [1, 1], [1, 0]));
        $this->assertEquals($expectedQuery, $expr->getQuery());
    }

    public function testSetWithAtomic(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a')->set(1, true));
        $this->assertEquals(['$set' => ['a' => 1]], $expr->getNewObj());
    }

    public function testSetWithoutAtomicWithTopLevelField(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a')->set(1, false));
        $this->assertEquals(['a' => 1], $expr->getNewObj());
    }

    public function testSetWithoutAtomicWithNestedField(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a.b.c')->set(1, false));
        $this->assertEquals(['a' => ['b' => ['c' => 1]]], $expr->getNewObj());
    }

    public function testText(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->text('foo'));
        $this->assertEquals(['$text' => ['$search' => 'foo']], $expr->getQuery());
        $this->assertNull($expr->getCurrentField());
    }

    public function testWhere(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->where('javascript'));
        $this->assertEquals(['$where' => 'javascript'], $expr->getQuery());
        $this->assertNull($expr->getCurrentField());
    }

    public function testIn(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->in(['value1', 'value2']));
        $this->assertEquals(['$in' => ['value1', 'value2']], $expr->getQuery());
    }

    public function testInWillStripKeysToYieldBsonArray(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->in([1 => 'value1', 'some' => 'value2']));
        $this->assertEquals(['$in' => ['value1', 'value2']], $expr->getQuery());
    }

    public function testNotIn(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->notIn(['value1', 'value2']));
        $this->assertEquals(['$nin' => ['value1', 'value2']], $expr->getQuery());
    }

    public function testNotInWillStripKeysToYieldBsonArray(): void
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->notIn([1 => 'value1', 'some' => 'value2']));
        $this->assertEquals(['$nin' => ['value1', 'value2']], $expr->getQuery());
    }

    private function createExpr(): Expr
    {
        $expr = new Expr($this->dm);
        $expr->setClassMetadata($this->dm->getClassMetadata(User::class));

        return $expr;
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

    private function getMockPolygon($json)
    {
        $point = $this->getMockBuilder(Polygon::class)
            ->disableOriginalConstructor()
            ->getMock();

        $point->expects($this->once())
            ->method('jsonSerialize')
            ->willReturn($json);

        return $point;
    }
}
