<?php

namespace Doctrine\ODM\MongoDB\Tests\Query;

require_once __DIR__ . '/../../../../../TestInit.php';

use Doctrine\ODM\MongoDB\Query\Parser,
    Doctrine\ODM\MongoDB\Query;

class ParserTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();
        $this->parser = new Parser($this->dm);
    }

    public function testPushAllComplexJsonValue()
    {
        $group1 = new \stdClass;
        $group1->name = 'group1';
        $group2 = new \stdClass;
        $group2->name = 'group2';
        $query = $this->parser->parse('update Documents\User pushAll groups = \'[{"name":"group1"},{"name":"group2"}]\'');
        $this->assertEquals(array('$pushAll' => array('groups' => array($group1, $group2))), $query->debug('newObj'));
    }

    public function testPlaceholders()
    {
        $query = $this->dm->query('find all Documents\User where username = ? and password = ?', array('jwage', 'changeme'));
        $this->assertEquals(array('username' => 'jwage', 'password' => 'changeme'), $query->debug('where'));
    }

    public function testWhereInJsonValue()
    {
        $query = $this->parser->parse("find all Documents\User where groups in '[1, 2, 3]'");
        $this->assertEquals(array('groups' => array('$in' => array(1, 2, 3))), $query->debug('where'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMixingException()
    {
        $query = $this->dm->query('find all Documents\User where username = ? and password = :password', array('jwage', ':password' => 'changeme'));
    }

    public function testPushAllJsonValue()
    {
        $query = $this->parser->parse("update Documents\User pushAll groups = '[1, 2, 3]'");
        $this->assertEquals(array('$pushAll' => array('groups' => array(1, 2, 3))), $query->debug('newObj'));
    }

    public function testPushAllOperator()
    {
        $query = $this->parser->parse("update Documents\User pushAll groups = :groups", array(':groups' => array(1, 2, 3)));
        $this->assertEquals(array('$pushAll' => array('groups' => array(1, 2, 3))), $query->debug('newObj'));
    }

    public function testMultipleOperators()
    {
        $query = $this->dm->query("update Documents\User set username = 'jwage', set password = 'changeme', inc count = 1, push groups = 1");
        $this->assertEquals(array('$set' => array('username' => 'jwage', 'password' => 'changeme'), '$inc' => array('count' => 1), '$push' => array('groups' => 1)), $query->debug('newObj'));
    }

    public function testNotEquals()
    {
        $query = $this->parser->parse("find all Documents\User where username != 'jwage' and username != 'ok'");
        $this->assertEquals(array('username' => array('$ne' => array('jwage', 'ok'))), $query->debug('where'));
    }

    public function testReduce()
    {
        $query = $this->parser->parse("find all Documents\User reduce 'function () { return this.a == 3 || this.b == 4; }'");
        $this->assertEquals(array('reduce' => 'function () { return this.a == 3 || this.b == 4; }'), $query->debug('mapReduce'));
    }

    public function testMapAndReduce()
    {
        $query = $this->parser->parse("find all Documents\User map 'function () { return 1; }' reduce 'function () { return this.a == 3 || this.b == 4; }'");
        $this->assertEquals(array('map' => 'function () { return 1; }', 'reduce' => 'function () { return this.a == 3 || this.b == 4; }'), $query->debug('mapReduce'));
    }

    public function testInsert()
    {
        $query = $this->parser->parse("insert Documents\User set username = 'jwage', password = 'changeme'");
        $this->assertEquals(Query::TYPE_INSERT, $query->debug('type'));
        $this->assertEquals(array('username' => 'jwage', 'password' => 'changeme'), $query->debug('newObj'));
    }

    public function testSort()
    {
        $query = $this->parser->parse('find all Documents\User sort username asc, email desc');
        $this->assertEquals(array('username' => 1, 'email' => -1), $query->debug('sort'));
    }

    public function testLimit()
    {
        $query = $this->parser->parse('find all Documents\User sort username asc limit 10');
        $this->assertEquals(10, $query->debug('limit'));
    }

    public function testSkip()
    {
        $query = $this->parser->parse('find all Documents\User sort username asc skip 30 limit 10');
        $this->assertEquals(10, $query->debug('limit'));
        $this->assertEquals(30, $query->debug('skip'));
    }

    public function testRemove()
    {
        $query = $this->parser->parse("remove Documents\User where username = 'jwage'");
        $this->assertEquals(Query::TYPE_REMOVE, $query->debug('type'));
        $this->assertEquals(array('username' => 'jwage'), $query->debug('where'));
    }

    public function testUpdate()
    {
        $query = $this->parser->parse("update Documents\User set username = 'jwage', set password = 'changeme', set groups = '[1, 2, 3]'");
        $this->assertEquals(Query::TYPE_UPDATE, $query->debug('type'));
        $this->assertEquals(array('$set' => array('username' => 'jwage', 'password' => 'changeme', 'groups' => array(1, 2, 3))), $query->debug('newObj'));
    }

    public function testUpdateWithWhere()
    {
        $query = $this->parser->parse("update Documents\User set password = 'changeme' where username = 'jwage'");
        $this->assertEquals(array('username' => 'jwage'), $query->debug('where'));
    }

    public function testIncrementOperator()
    {
        $query = $this->parser->parse("update Documents\User inc count = 1, inc views = 2, set username = 'jwage'");
        $this->assertEquals(array('$set' => array('username' => 'jwage'), '$inc' => array('count' => 1, 'views' => 2)), $query->debug('newObj'));
    }

    public function testUnsetOperator()
    {
        $query = $this->parser->parse("update Documents\User unset somefield, unset anotherfield");
        $this->assertEquals(array('$unset' => array('somefield' => 1, 'anotherfield' => 1)), $query->debug('newObj'));
    }

    public function testPushOperator()
    {
        $query = $this->parser->parse("update Documents\User push groups = :group", array(':group' => 1));
        $this->assertEquals(array('$push' => array('groups' => 1)), $query->debug('newObj'));
    }

    public function testPullOperator()
    {
        $query = $this->parser->parse("update Documents\User pull groups = :groups", array(':groups' => array(1, 2, 3)));
        $this->assertEquals(array('$pull' => array('groups' => array(1, 2, 3))), $query->debug('newObj'));
    }

    public function testPullAllOperator()
    {
        $query = $this->parser->parse("update Documents\User pullAll groups = :groups", array(':groups' => array(1, 2, 3)));
        $this->assertEquals(array('$pullAll' => array('groups' => array(1, 2, 3))), $query->debug('newObj'));
    }

    public function testPopFirstOperator()
    {
        $query = $this->parser->parse("update Documents\User popFirst groups, popFirst comments");
        $this->assertEquals(array('$pop' => array('groups' => 1, 'comments' => 1)), $query->debug('newObj'));
    }

    public function testPopLastOperator()
    {
        $query = $this->parser->parse("update Documents\User popFirst groups, popLast comments");
        $this->assertEquals(array('$pop' => array('groups' => 1, 'comments' => -1)), $query->debug('newObj'));
    }

    public function testAddToSet()
    {
        $query = $this->parser->parse("update Documents\User addToSet groups = :group", array(':group' => 1));
        $this->assertEquals(array('$addToSet' => array('groups' => 1)), $query->debug('newObj'));
    }

    public function testAddManyToSet()
    {
        $query = $this->parser->parse("update Documents\User addManyToSet groups = :groups", array(':groups' => array(1, 2, 3)));
        $this->assertEquals(array('$addToSet' => array('groups' => array('$each' => array(1, 2, 3)))), $query->debug('newObj'));
    }

    public function testFind()
    {
        $query = $this->parser->parse("find all Documents\User");
        $this->assertEquals(Query::TYPE_FIND, $query->debug('type'));
    }

    public function testWhere()
    {
        $query = $this->parser->parse("find all Documents\User where username = 'jwage' and password = 'changeme'");
        $this->assertEquals(array('username' => 'jwage', 'password' => 'changeme'), $query->debug('where'));
    }

    public function testSelectAll()
    {
        $query = $this->parser->parse("find all Documents\User");
        $this->assertEquals(array(), $query->debug('select'));
    }

    public function testGreaterThan()
    {
        $query = $this->parser->parse('find username Documents\User where count > 1');
        $this->assertEquals(array('count' => array('$gt' => 1)), $query->debug('where'));
    }

    public function testGreaterThanOrEqualTo()
    {
        $query = $this->parser->parse('find username Documents\User where count >= 1');
        $this->assertEquals(array('count' => array('$gte' => 1)), $query->debug('where'));
    }

    public function testLessThan()
    {
        $query = $this->parser->parse('find username Documents\User where count < 1');
        $this->assertEquals(array('count' => array('$lt' => 1)), $query->debug('where'));
    }

    public function testLessThanOrEqualTo()
    {
        $query = $this->parser->parse('find username Documents\User where count <= 1');
        $this->assertEquals(array('count' => array('$lte' => 1)), $query->debug('where'));
    }

    public function testFindSpecificFields()
    {
        $query = $this->parser->parse('find username, password Documents\User');
        $this->assertEquals(array('username', 'password'), $query->debug('select'));
        $this->assertEquals('Documents\User', $query->debug('className'));
    }

    public function testFindAllFields()
    {
        $query = $this->parser->parse('find all Documents\User');
        $this->assertEquals(array(), $query->debug('select'));
    }

    public function testLiteralValuesInWhere()
    {
        $query = $this->parser->parse("find all Documents\User where username = 'jwage' AND password = 'changeme' AND isActive = true AND isNew = false");
        $this->assertEquals(array('username' => 'jwage', 'password' => 'changeme', 'isActive' => true, 'isNew' => false), $query->debug('where'));
    }

    public function testWhereIn()
    {
        $query = $this->parser->parse("find all Documents\User where groups in :groups", array(':groups' => array(1, 2, 3)));
        $this->assertEquals(array('groups' => array('$in' => array(1, 2, 3))), $query->debug('where'));
    }

    public function testWhereNotIn()
    {
        $query = $this->parser->parse('find all Documents\User where groups notIn :groups', array(':groups' => array(1, 2, 3)));
        $this->assertEquals(array('groups' => array('$nin' => array(1, 2, 3))), $query->debug('where'));
    }

    public function testWhereAll()
    {
        $query = $this->parser->parse('find all Documents\User where groups all :groups', array(':groups' => array(1, 2, 3)));
        $this->assertEquals(array('groups' => array('$all' => array(1, 2, 3))), $query->debug('where'));
    }

    public function testWhereSize()
    {
        $query = $this->parser->parse('find all Documents\User where groups size 3');
        $this->assertEquals(array('groups' => array('$size' => 3)), $query->debug('where'));   
    }

    public function testWhereExists()
    {
        $query = $this->parser->parse('find all Documents\User where groups exists true and comments exists false');
        $this->assertEquals(array('groups' => array('$exists' => true), 'comments' => array('$exists' => false)), $query->debug('where'));   
    }

    public function testWhereType()
    {
        $query = $this->parser->parse('find all Documents\User where username type string');
        $this->assertEquals(array('username' => array('$type' => 2)), $query->debug('where'));   
    }
}