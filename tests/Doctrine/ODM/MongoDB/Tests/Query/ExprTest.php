<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadataInfo;
use Doctrine\ODM\MongoDB\Persisters\DocumentPersister;
use Doctrine\ODM\MongoDB\Query\Expr;
use Doctrine\ODM\MongoDB\UnitOfWork;
use Documents\User;

class ExprTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSelectIsPrepared()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->select('id');
        $query = $qb->getQuery();

        $this->assertEquals(array('_id' => 1), $query->debug('select'));
    }

    public function testInIsPrepared()
    {
        $ids = array('4f28aa84acee41388900000a');

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->in($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf(\MongoDB\BSON\ObjectId::class, $debug['groups.$id']['$in'][0]);
        $this->assertEquals($ids[0], (string) $debug['groups.$id']['$in'][0]);
    }

    public function testAllIsPrepared()
    {
        $ids = array('4f28aa84acee41388900000a');

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->all($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf(\MongoDB\BSON\ObjectId::class, $debug['groups.$id']['$all'][0]);
        $this->assertEquals($ids[0], (string) $debug['groups.$id']['$all'][0]);
    }

    public function testNotEqualIsPrepared()
    {
        $id = '4f28aa84acee41388900000a';

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->notEqual($id)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf(\MongoDB\BSON\ObjectId::class, $debug['groups.$id']['$ne']);
        $this->assertEquals($id, (string) $debug['groups.$id']['$ne']);
    }

    public function testNotInIsPrepared()
    {
        $ids = array('4f28aa84acee41388900000a');

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->notIn($ids)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf(\MongoDB\BSON\ObjectId::class, $debug['groups.$id']['$nin'][0]);
        $this->assertEquals($ids[0], (string) $debug['groups.$id']['$nin'][0]);
    }

    public function testAndIsPrepared()
    {
        $ids = array('4f28aa84acee41388900000a');

        $qb = $this->dm->createQueryBuilder('Documents\User');
        $qb
            ->addAnd($qb->expr()->field('groups.id')->in($ids))
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf(\MongoDB\BSON\ObjectId::class, $debug['$and'][0]['groups.$id']['$in'][0]);
        $this->assertEquals($ids[0], (string) $debug['$and'][0]['groups.$id']['$in'][0]);
    }

    public function testOrIsPrepared()
    {
        $ids = array('4f28aa84acee41388900000a');

        $qb = $this->dm->createQueryBuilder('Documents\User');
        $qb
            ->addOr($qb->expr()->field('groups.id')->in($ids))
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf(\MongoDB\BSON\ObjectId::class, $debug['$or'][0]['groups.$id']['$in'][0]);
        $this->assertEquals($ids[0], (string) $debug['$or'][0]['groups.$id']['$in'][0]);
    }

    public function testMultipleQueryOperatorsArePrepared()
    {
        $all = array('4f28aa84acee41388900000a');
        $in = array('4f28aa84acee41388900000b');
        $ne = '4f28aa84acee41388900000c';
        $nin = array('4f28aa84acee41388900000d');

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('groups.id')->all($all)
            ->field('groups.id')->in($in)
            ->field('groups.id')->notEqual($ne)
            ->field('groups.id')->notIn($nin)
            ->select('id')->hydrate(false);
        $query = $qb->getQuery();
        $debug = $query->debug('query');

        $this->assertInstanceOf(\MongoDB\BSON\ObjectId::class, $debug['groups.$id']['$all'][0]);
        $this->assertEquals($all[0], (string) $debug['groups.$id']['$all'][0]);
        $this->assertInstanceOf(\MongoDB\BSON\ObjectId::class, $debug['groups.$id']['$in'][0]);
        $this->assertEquals($in[0], (string) $debug['groups.$id']['$in'][0]);
        $this->assertInstanceOf(\MongoDB\BSON\ObjectId::class, $debug['groups.$id']['$ne']);
        $this->assertEquals($ne, (string) $debug['groups.$id']['$ne']);
        $this->assertInstanceOf(\MongoDB\BSON\ObjectId::class, $debug['groups.$id']['$nin'][0]);
        $this->assertEquals($nin[0], (string) $debug['groups.$id']['$nin'][0]);
    }

    public function testPrepareNestedDocuments()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('address.subAddress.subAddress.subAddress.test')->equals('test');
        $query = $qb->getQuery();
        $debug = $query->debug('query');
        $this->assertEquals(array('address.subAddress.subAddress.subAddress.testFieldName' => 'test'), $debug);
    }

    public function testPreparePositionalOperator()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->updateOne()
            ->field('phonenumbers.$.phonenumber')->equals('foo')
            ->field('phonenumbers.$')->set(array('phonenumber' => 'bar'));

        $this->assertEquals(array('phonenumbers.$.phonenumber' => 'foo'), $qb->getQueryArray());
        $this->assertEquals(array('$set' => array('phonenumbers.$' => array('phonenumber' => 'bar'))), $qb->getNewObj());
    }

    public function testSortIsPrepared()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->sort('id', 'desc');
        $query = $qb->getQuery();
        $query = $query->getQuery();
        $this->assertEquals(array('_id' => -1), $query['sort']);

        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->sort('address.subAddress.subAddress.subAddress.test', 'asc');
        $query = $qb->getQuery();
        $query = $query->getQuery();
        $this->assertEquals(array('address.subAddress.subAddress.subAddress.testFieldName' => 1), $query['sort']);
    }

    public function testNestedWithOperator()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->field('address.subAddress.subAddress.subAddress.test')->notIn(array('test'));
        $query = $qb->getQuery();
        $query = $query->getQuery();
        $this->assertEquals(array('address.subAddress.subAddress.subAddress.testFieldName' => array('$nin' => array('test'))), $query['query']);
    }

    public function testNewObjectIsPrepared()
    {
        $qb = $this->dm->createQueryBuilder('Documents\User')
            ->updateOne()
            ->field('address.subAddress.subAddress.subAddress.test')->popFirst();
        $query = $qb->getQuery();
        $query = $query->getQuery();
        $this->assertEquals(array('$pop' => array('address.subAddress.subAddress.subAddress.testFieldName' => 1)), $query['newObj']);
    }

    public function testReferencesUsesMinimalKeys()
    {
        $dm = $this->createMock(DocumentManager::class);
        $uow = $this->createMock(UnitOfWork::class);
        $documentPersister = $this->createMock(DocumentPersister::class);
        $class = $this->createMock(ClassMetadata::class);

        $expected = array('foo.$id' => '1234');

        $dm
            ->expects($this->once())
            ->method('createReference')
            ->will($this->returnValue(array('$ref' => 'coll', '$id' => '1234', '$db' => 'db')));
        $dm
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $uow
            ->expects($this->once())
            ->method('getDocumentPersister')
            ->will($this->returnValue($documentPersister));

        $documentPersister
            ->expects($this->once())
            ->method('prepareQueryOrNewObj')
            ->with($expected)
            ->will($this->returnValue($expected));

        $class
            ->expects($this->once())
            ->method('getFieldMapping')
            ->will($this->returnValue(array('targetDocument' => 'Foo', 'name' => 'foo', 'storeAs' => ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF_WITH_DB)));

        $expr = $this->createExpr($dm, $class);
        $expr->field('bar')->references(new \stdClass());

        $this->assertEquals($expected, $expr->getQuery(), '->references() uses just $id if a targetDocument is set');
    }

    public function testReferencesUsesAllKeys()
    {
        $dm = $this->createMock(DocumentManager::class);
        $uow = $this->createMock(UnitOfWork::class);
        $documentPersister = $this->createMock(DocumentPersister::class);
        $class = $this->createMock(ClassMetadata::class);

        $expected = array('foo.$ref' => 'coll', 'foo.$id' => '1234', 'foo.$db' => 'db');

        $dm
            ->expects($this->once())
            ->method('createReference')
            ->will($this->returnValue(array('$ref' => 'coll', '$id' => '1234', '$db' => 'db')));
        $dm
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $uow
            ->expects($this->once())
            ->method('getDocumentPersister')
            ->will($this->returnValue($documentPersister));

        $documentPersister
            ->expects($this->once())
            ->method('prepareQueryOrNewObj')
            ->with($expected)
            ->will($this->returnValue($expected));

        $class
            ->expects($this->once())
            ->method('getFieldMapping')
            ->will($this->returnValue(array('name' => 'foo', 'storeAs' => ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF_WITH_DB)));

        $expr = $this->createExpr($dm, $class);
        $expr->field('bar')->references(new \stdClass());

        $this->assertEquals($expected, $expr->getQuery(), '->references() uses all keys if no targetDocument is set');
    }

    public function testReferencesUsesSomeKeys()
    {
        $dm = $this->createMock(DocumentManager::class);
        $uow = $this->createMock(UnitOfWork::class);
        $documentPersister = $this->createMock(DocumentPersister::class);
        $class = $this->createMock(ClassMetadata::class);

        $expected = array('foo.$ref' => 'coll', 'foo.$id' => '1234');

        $dm
            ->expects($this->once())
            ->method('createReference')
            ->will($this->returnValue(array('$ref' => 'coll', '$id' => '1234')));
        $dm
            ->expects($this->once())
            ->method('getUnitOfWork')
            ->will($this->returnValue($uow));

        $uow
            ->expects($this->once())
            ->method('getDocumentPersister')
            ->will($this->returnValue($documentPersister));

        $documentPersister
            ->expects($this->once())
            ->method('prepareQueryOrNewObj')
            ->with($expected)
            ->will($this->returnValue($expected));

        $class
            ->expects($this->once())
            ->method('getFieldMapping')
            ->will($this->returnValue(array('storeAs' => ClassMetadataInfo::REFERENCE_STORE_AS_DB_REF, 'name' => 'foo')));

        $expr = $this->createExpr($dm, $class);
        $expr->field('bar')->references(new \stdClass());

        $this->assertEquals($expected, $expr->getQuery(), '->references() uses some keys if storeAs=dbRef is set');
    }

    public function testAddManyToSet()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a')->addManyToSet([1, 2]));
        $this->assertEquals(['$addToSet' => ['a' => ['$each' => [1, 2]]]], $expr->getNewObj());
    }

    public function testAddToSetWithValue()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a')->addToSet(1));
        $this->assertEquals(['$addToSet' => ['a' => 1]], $expr->getNewObj());
    }

    public function testAddToSetWithExpression()
    {
        $expr = $this->createExpr();
        $eachExpr = $this->createExpr();
        $eachExpr->each([1, 2]);

        $this->assertSame($expr, $expr->field('a')->addToSet($eachExpr));
        $this->assertEquals(['$addToSet' => ['a' => ['$each' => [1, 2]]]], $expr->getNewObj());
    }

    public function testLanguageWithText()
    {
        $expr = $this->createExpr();
        $expr->text('foo');

        $this->assertSame($expr, $expr->language('en'));
        $this->assertEquals(['$text' => ['$search' => 'foo', '$language' => 'en']], $expr->getQuery());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testLanguageRequiresTextOperator()
    {
        $expr = $this->createExpr();
        $expr->language('en');
    }

    public function testCaseSensitiveWithText()
    {
        $expr = $this->createExpr();
        $expr->text('foo');

        $this->assertSame($expr, $expr->caseSensitive(true));
        $this->assertEquals(['$text' => ['$search' => 'foo', '$caseSensitive' => true]], $expr->getQuery());
    }

    public function testCaseSensitiveFalseRemovesOption()
    {
        $expr = $this->createExpr();
        $expr->text('foo');

        $expr->caseSensitive(true);
        $expr->caseSensitive(false);
        $this->assertEquals(['$text' => ['$search' => 'foo']], $expr->getQuery());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testCaseSensitiveRequiresTextOperator()
    {
        $expr = $this->createExpr();
        $expr->caseSensitive('en');
    }

    public function testDiacriticSensitiveWithText()
    {
        $expr = $this->createExpr();
        $expr->text('foo');

        $this->assertSame($expr, $expr->diacriticSensitive(true));
        $this->assertEquals(['$text' => ['$search' => 'foo', '$diacriticSensitive' => true]], $expr->getQuery());
    }

    public function testDiacriticSensitiveFalseRemovesOption()
    {
        $expr = $this->createExpr();
        $expr->text('foo');

        $expr->diacriticSensitive(true);
        $expr->diacriticSensitive(false);
        $this->assertEquals(['$text' => ['$search' => 'foo']], $expr->getQuery());
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testDiacriticSensitiveRequiresTextOperator()
    {
        $expr = $this->createExpr();
        $expr->diacriticSensitive('en');
    }

    public function testOperatorWithCurrentField()
    {
        $expr = $this->createExpr();
        $expr->field('field');

        $this->assertSame($expr, $expr->operator('$op', 'value'));
        $this->assertEquals(['field' => ['$op' => 'value']], $expr->getQuery());
    }

    public function testOperatorWithCurrentFieldWrapsEqualityCriteria()
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

    public function testOperatorWithoutCurrentField()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->operator('$op', 'value'));
        $this->assertEquals(['$op' => 'value'], $expr->getQuery());
    }

    public function testOperatorWithoutCurrentFieldWrapsEqualityCriteria()
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

    public function provideGeoJsonPoint()
    {
        $json = ['type' => 'Point', 'coordinates' => [1, 2]];
        $expected = ['$geometry' => $json];

        return [
            'array' => [$json, $expected],
            'object' => [$this->getMockPoint($json), $expected],
        ];
    }

    /**
     * @dataProvider provideGeoJsonPoint
     */
    public function testNearWithGeoJsonPoint($point, array $expected)
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->near($point));
        $this->assertEquals(['$near' => $expected], $expr->getQuery());
    }

    public function testNearWithLegacyCoordinates()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->near(1, 2));
        $this->assertEquals(['$near' => [1, 2]], $expr->getQuery());
    }

    /**
     * @dataProvider provideGeoJsonPoint
     */
    public function testNearSphereWithGeoJsonPoint($point, array $expected)
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->nearSphere($point));
        $this->assertEquals(['$nearSphere' => $expected], $expr->getQuery());
    }

    public function testNearSphereWithLegacyCoordinates()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->nearSphere(1, 2));
        $this->assertEquals(['$nearSphere' => [1, 2]], $expr->getQuery());
    }

    public function testPullWithValue()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a')->pull(1));
        $this->assertEquals(['$pull' => ['a' => 1]], $expr->getNewObj());
    }

    public function testPullWithExpression()
    {
        $expr = $this->createExpr();
        $nestedExpr = $this->createExpr();
        $nestedExpr->gt(3);

        $this->assertSame($expr, $expr->field('a')->pull($nestedExpr));
        $this->assertEquals(['$pull' => ['a' => ['$gt' => 3]]], $expr->getNewObj());
    }

    public function testPushWithValue()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a')->push(1));
        $this->assertEquals(['$push' => ['a' => 1]], $expr->getNewObj());
    }

    public function testPushWithExpression()
    {
        $expr = $this->createExpr();
        $innerExpr = $this->createExpr();
        $innerExpr
            ->each([['x' => 1], ['x' => 2]])
            ->slice(-2)
            ->sort('x', 1);

        $expectedNewObj = [
            '$push' => ['a' => [
                '$each' => [['x' => 1], ['x' => 2]],
                '$slice' => -2,
                '$sort' => ['x' => 1],
            ]],
        ];

        $this->assertSame($expr, $expr->field('a')->push($innerExpr));
        $this->assertEquals($expectedNewObj, $expr->getNewObj());
    }

    public function testPushWithExpressionShouldEnsureEachOperatorAppearsFirst()
    {
        $expr = $this->createExpr();
        $innerExpr = $this->createExpr();
        $innerExpr
            ->sort('x', 1)
            ->slice(-2)
            ->each([['x' => 1], ['x' => 2]]);

        $expectedNewObj = [
            '$push' => ['a' => [
                '$each' => [['x' => 1], ['x' => 2]],
                '$sort' => ['x' => 1],
                '$slice' => -2,
            ]],
        ];

        $this->assertSame($expr, $expr->field('a')->push($innerExpr));
        $this->assertSame($expectedNewObj, $expr->getNewObj());
    }

    public function testPushWithPosition()
    {
        $expr = $this->createExpr();
        $innerExpr = $this->createExpr();
        $innerExpr
            ->each([20, 30])
            ->position(0);

        $expectedNewObj = [
            '$push' => ['a' => [
                '$each' => [20, 30],
                '$position' => 0,
            ]],
        ];

        $this->assertSame($expr, $expr->field('a')->push($innerExpr));
        $this->assertEquals($expectedNewObj, $expr->getNewObj());
    }

    /**
     * @dataProvider provideGeoJsonPolygon
     */
    public function testGeoIntersects($geometry, array $expected)
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->geoIntersects($geometry));
        $this->assertEquals(['$geoIntersects' => $expected], $expr->getQuery());
    }

    public function provideGeoJsonPolygon()
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
    public function testGeoWithin($geometry, array $expected)
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->geoWithin($geometry));
        $this->assertEquals(['$geoWithin' => $expected], $expr->getQuery());
    }

    public function testGeoWithinBox()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->geoWithinBox(1, 2, 3, 4));
        $this->assertEquals(['$geoWithin' => ['$box' => [[1, 2], [3, 4]]]], $expr->getQuery());
    }

    public function testGeoWithinCenter()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->geoWithinCenter(1, 2, 3));
        $this->assertEquals(['$geoWithin' => ['$center' => [[1, 2], 3]]], $expr->getQuery());
    }

    public function testGeoWithinCenterSphere()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->geoWithinCenterSphere(1, 2, 3));
        $this->assertEquals(['$geoWithin' => ['$centerSphere' => [[1, 2], 3]]], $expr->getQuery());
    }

    public function testGeoWithinPolygon()
    {
        $expr = $this->createExpr();
        $expectedQuery = ['$geoWithin' => ['$polygon' => [[0, 0], [1, 1], [1, 0]]]];

        $this->assertSame($expr, $expr->geoWithinPolygon([0, 0], [1, 1], [1, 0]));
        $this->assertEquals($expectedQuery, $expr->getQuery());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGeoWithinPolygonRequiresAtLeastThreePoints()
    {
        $expr = $this->createExpr();
        $expr->geoWithinPolygon([0, 0], [1, 1]);
    }

    public function testSetWithAtomic()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a')->set(1, true));
        $this->assertEquals(['$set' => ['a' => 1]], $expr->getNewObj());
    }

    public function testSetWithoutAtomicWithTopLevelField()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a')->set(1, false));
        $this->assertEquals(['a' => 1], $expr->getNewObj());
    }

    public function testSetWithoutAtomicWithNestedField()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->field('a.b.c')->set(1, false));
        $this->assertEquals(['a' => ['b' => ['c' => 1]]], $expr->getNewObj());
    }

    public function testText()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->text('foo'));
        $this->assertEquals(['$text' => ['$search' => 'foo']], $expr->getQuery());
        $this->assertNull($expr->getCurrentField());
    }

    public function testWhere()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->where('javascript'));
        $this->assertEquals(['$where' => 'javascript'], $expr->getQuery());
        $this->assertNull($expr->getCurrentField());
    }

    public function testWithinBox()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->withinBox(1, 2, 3, 4));
        $this->assertEquals(['$within' => ['$box' => [[1, 2], [3, 4]]]], $expr->getQuery());
    }

    public function testWithinCenter()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->withinCenter(1, 2, 3));
        $this->assertEquals(['$within' => ['$center' => [[1, 2], 3]]], $expr->getQuery());
    }

    public function testWithinCenterSphere()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->withinCenterSphere(1, 2, 3));
        $this->assertEquals(['$within' => ['$centerSphere' => [[1, 2], 3]]], $expr->getQuery());
    }

    public function testWithinPolygon()
    {
        $expr = $this->createExpr();
        $expectedQuery = ['$within' => ['$polygon' => [[0, 0], [1, 1], [1, 0]]]];

        $this->assertSame($expr, $expr->withinPolygon([0, 0], [1, 1], [1, 0]));
        $this->assertEquals($expectedQuery, $expr->getQuery());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWithinPolygonRequiresAtLeastThreePoints()
    {
        $expr = $this->createExpr();
        $expr->withinPolygon([0, 0], [1, 1]);
    }

    public function testIn()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->in(['value1', 'value2']));
        $this->assertEquals(['$in' => ['value1', 'value2']], $expr->getQuery());
    }

    public function testInWillStripKeysToYieldBsonArray()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->in([1 => 'value1', 'some' => 'value2']));
        $this->assertEquals(['$in' => ['value1', 'value2']], $expr->getQuery());
    }

    public function testNotIn()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->notIn(['value1', 'value2']));
        $this->assertEquals(['$nin' => ['value1', 'value2']], $expr->getQuery());
    }

    public function testNotInWillStripKeysToYieldBsonArray()
    {
        $expr = $this->createExpr();

        $this->assertSame($expr, $expr->notIn([1 => 'value1', 'some' => 'value2']));
        $this->assertEquals(['$nin' => ['value1', 'value2']], $expr->getQuery());
    }

    private function createExpr(DocumentManager $dm = null, ClassMetadata $class = null): Expr
    {
        if (!$dm) {
            $dm = $this->createMock(DocumentManager::class);
            $uow = $this->createMock(UnitOfWork::class);
            $documentPersister = $this->createMock(DocumentPersister::class);

            $dm
                ->expects($this->any())
                ->method('getUnitOfWork')
                ->will($this->returnValue($uow));

            $uow
                ->expects($this->any())
                ->method('getDocumentPersister')
                ->will($this->returnValue($documentPersister));

            $documentPersister
                ->expects($this->any())
                ->method('prepareQueryOrNewObj')
                ->will($this->returnArgument(0));
        }

        if (!$class) {
            $class = new ClassMetadata(User::class);
        }

        $expr = new Expr($dm);
        $expr->setClassMetadata($class);

        return $expr;
    }

    private function getMockPoint($json)
    {
        $point = $this->getMockBuilder('GeoJson\Geometry\Point')
            ->disableOriginalConstructor()
            ->getMock();

        $point->expects($this->once())
            ->method('jsonSerialize')
            ->will($this->returnValue($json));

        return $point;
    }

    private function getMockPolygon($json)
    {
        $point = $this->getMockBuilder('GeoJson\Geometry\Polygon')
            ->disableOriginalConstructor()
            ->getMock();

        $point->expects($this->once())
            ->method('jsonSerialize')
            ->will($this->returnValue($json));

        return $point;
    }
}
