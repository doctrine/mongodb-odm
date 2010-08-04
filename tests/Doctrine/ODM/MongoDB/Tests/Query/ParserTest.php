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

    public function testDistinct()
    {
        $query = $this->parser->parse('find distinct count Documents\User');
        $this->assertEquals('count', $query->debug('distinctField'));
    }

    /**
     * @expectedException Doctrine\ODM\MongoDB\MongoDBException
     */
    public function testMultipleDistinctThrowsException()
    {
        $query = $this->parser->parse('find distinct count, distinct test Documents\User');
    }

    public function testSelectSlice()
    {
        $query = $this->parser->parse('find username, profile.firstName, comments limit 20 skip 10 Documents\User');
        $this->assertEquals(array('username', 'profile.firstName', 'comments' => array($this->escape('slice') => array(10, 20))), $query->debug('select'));

        $query = $this->parser->parse('find comments skip 10 Documents\User');
        $this->assertEquals(array('comments' => array($this->escape('slice') => array(10))), $query->debug('select'));

        $query = $this->parser->parse('find comments limit 10 Documents\User');
        $this->assertEquals(array('comments' => array($this->escape('slice') => array(0, 10))), $query->debug('select'));
    }

    public function testWhereMod()
    {
        $query = $this->parser->parse("find all Documents\User where a mod '[10, 1]'");
        $this->assertEquals(array('a' => array($this->escape('mod') => array(10, 1))), $query->debug('where'));

        $query = $this->parser->parse("find all Documents\User where not a mod '[10, 1]'");
        $this->assertEquals(array('a' => array($this->escape('not') => array($this->escape('mod') => array(10, 1)))), $query->debug('where'));
    }

    public function testWhereNot()
    {
        $query = $this->parser->parse("find all Documents\User where not username = 'jwage' and not count > 1 and not groups in '[1, 2, 3]'");
        $this->assertEquals(array(
            'username' => array($this->escape('not') => 'jwage'),
            'count' => array($this->escape('not') => array($this->escape('gt') => 1)),
            'groups' => array($this->escape('not') => array($this->escape('in') => array(1, 2, 3)))
        ), $query->debug('where'));
    }

    public function testElemMatch()
    {
        $query = $this->parser->parse("find all Documents\User where phonenumbers.phonenumber in '[1]'");
        $this->assertEquals(
            array('phonenumbers.phonenumber' => array(
                $this->escape('in') => array(1)
            )),
            $query->debug('where')
        );

        $query = $this->parser->parse("find all Documents\User where all accounts.name = 'test' and all accounts.type_name = 'test' and accounts.name = 'test'");
        $this->assertEquals(
            array(
                'accounts' => array(
                    $this->escape('elemMatch') => array(
                        'name' => 'test',
                        'type_name' => 'test'
                    )
                ),
                'accounts.name' => 'test'
            ),
            $query->debug('where')
        );
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testMixingException()
    {
        $query = $this->dm->query('find all Documents\User where username = ? and password = :password', array('jwage', ':password' => 'changeme'));
    }

    public function testComplexQuery()
    {
        $dql = 'find all Documents\User limit 10 skip 30
            sort username desc, password asc';
        $query = $this->parser->parse($dql);
        $this->assertEquals(10, $query->debug('limit'));
        $this->assertEquals(30, $query->debug('skip'));
        $this->assertEquals(array('username' => -1, 'password' => 1), $query->debug('sort'));

        $dql = "update Documents\User
            set field1 = 1,
            unset field2,
            inc field3 = 1,
            push groups = 1,
            pushAll groups = '[2, 3, 4]',
            pull groups = 1,
            pullAll groups = '[2]',
            popFirst groups,
            popLast comments,
            addToSet groups = 4,
            addManyToSet groups = '[5, 6, 7]'
            WHERE
            field1 = 'jwage'
            AND field2 = 'changeme'
            AND field3 != 'bob'
            AND field4 > 1
            AND field5 >= 1
            AND field6 < 5
            AND field7 > 10
            AND field8 in '[1, 2, 3, 4]'
            AND field9 notIn '[5, 6]',
            AND field10 all '[1, 2]',
            AND field11 size 5
            AND field12 exists true
            AND field13 type 'string'
            limit 10 skip 30
            sort username desc, password asc";
        $query = $this->parser->parse($dql);
        $this->assertEquals(array(
            'field1' => 'jwage',
            'field2' => 'changeme',
            'field3' => array(
              $this->escape('ne') => 'bob',
            ),
            'field4' => array(
              $this->escape('gt') => 1
            ),
            'field5' => array(
              $this->escape('gte') => 1
            ),
            'field6' => array(
              $this->escape('lt') => 5
            ),
            'field7' => array(
              $this->escape('gt') => 10
            ),
            'field8' => array(
              $this->escape('in') => array(1, 2, 3, 4)
            ),
            'field9' => array(
              $this->escape('nin') => array(5, 6)
            )
          ), $query->debug('where'));

        $this->assertEquals(array(
            $this->escape('set') => array(
              'field1' => 1,
            ),
            $this->escape('unset') => array(
              'field2' => 1,
            ),
            $this->escape('inc') => array(
              'field3' => 1,
            ),
            $this->escape('push') => array(
              'groups' => 1,
            ),
            $this->escape('pushAll') => array(
              'groups' => array(2, 3, 4),
            ),
            $this->escape('pull') => array(
              'groups' => 1,
            ),
            $this->escape('pullAll') => array(
              'groups' => array(2),
            ),
            $this->escape('pop') => array(
              'groups' => 1,
              'comments' => -1,
            ),
            $this->escape('addToSet') => array(
              'groups' => array(
                $this->escape('each') => array(4, 5, 6, 7),
              ),
            ),
          ), $query->debug('newObj'));
    }

    public function testPushAllComplexJsonValue()
    {
        $group1 = new \stdClass;
        $group1->name = 'group1';
        $group2 = new \stdClass;
        $group2->name = 'group2';
        $query = $this->parser->parse('update Documents\User pushAll groups = \'[{"name":"group1"},{"name":"group2"}]\'');
        $this->assertEquals(array($this->escape('pushAll') => array('groups' => array($group1, $group2))), $query->debug('newObj'));
    }

    public function testPlaceholders()
    {
        $query = $this->dm->query('find all Documents\User where username = ? and password = ?', array('jwage', 'changeme'));
        $this->assertEquals(array('username' => 'jwage', 'password' => 'changeme'), $query->debug('where'));
    }

    public function testWhereInJsonValue()
    {
        $query = $this->parser->parse("find all Documents\User where groups in '[1, 2, 3]'");
        $this->assertEquals(array('groups' => array($this->escape('in') => array(1, 2, 3))), $query->debug('where'));
    }

    public function testPushAllJsonValue()
    {
        $query = $this->parser->parse("update Documents\User pushAll groups = '[1, 2, 3]'");
        $this->assertEquals(array($this->escape('pushAll') => array('groups' => array(1, 2, 3))), $query->debug('newObj'));
    }

    public function testPushAllOperator()
    {
        $query = $this->parser->parse("update Documents\User pushAll groups = :groups", array(':groups' => array(1, 2, 3)));
        $this->assertEquals(array($this->escape('pushAll') => array('groups' => array(1, 2, 3))), $query->debug('newObj'));
    }

    public function testMultipleOperators()
    {
        $query = $this->dm->query("update Documents\User set username = 'jwage', set password = 'changeme', inc count = 1, push groups = 1");
        $this->assertEquals(array($this->escape('set') => array('username' => 'jwage', 'password' => 'changeme'), $this->escape('inc') => array('count' => 1), $this->escape('push') => array('groups' => 1)), $query->debug('newObj'));
    }

    public function testNotEquals()
    {
        $query = $this->parser->parse("find all Documents\User where username != 'jwage'");
        $this->assertEquals(array('username' => array($this->escape('ne') => 'jwage')), $query->debug('where'));
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
        $this->assertEquals(array($this->escape('set') => array('username' => 'jwage', 'password' => 'changeme', 'groups' => array(1, 2, 3))), $query->debug('newObj'));
    }

    public function testUpdateWithWhere()
    {
        $query = $this->parser->parse("update Documents\User set password = 'changeme' where username = 'jwage'");
        $this->assertEquals(array('username' => 'jwage'), $query->debug('where'));
    }

    public function testIncrementOperator()
    {
        $query = $this->parser->parse("update Documents\User inc count = 1, inc views = 2, set username = 'jwage'");
        $this->assertEquals(array($this->escape('set') => array('username' => 'jwage'), $this->escape('inc') => array('count' => 1, 'views' => 2)), $query->debug('newObj'));
    }

    public function testUnsetOperator()
    {
        $query = $this->parser->parse("update Documents\User unset somefield, unset anotherfield");
        $this->assertEquals(array($this->escape('unset') => array('somefield' => 1, 'anotherfield' => 1)), $query->debug('newObj'));
    }

    public function testPushOperator()
    {
        $query = $this->parser->parse("update Documents\User push groups = :group", array(':group' => 1));
        $this->assertEquals(array($this->escape('push') => array('groups' => 1)), $query->debug('newObj'));
    }

    public function testPullOperator()
    {
        $query = $this->parser->parse("update Documents\User pull groups = :groups", array(':groups' => array(1, 2, 3)));
        $this->assertEquals(array($this->escape('pull') => array('groups' => array(1, 2, 3))), $query->debug('newObj'));
    }

    public function testPullAllOperator()
    {
        $query = $this->parser->parse("update Documents\User pullAll groups = :groups", array(':groups' => array(1, 2, 3)));
        $this->assertEquals(array($this->escape('pullAll') => array('groups' => array(1, 2, 3))), $query->debug('newObj'));
    }

    public function testPopFirstOperator()
    {
        $query = $this->parser->parse("update Documents\User popFirst groups, popFirst comments");
        $this->assertEquals(array($this->escape('pop') => array('groups' => 1, 'comments' => 1)), $query->debug('newObj'));
    }

    public function testPopLastOperator()
    {
        $query = $this->parser->parse("update Documents\User popFirst groups, popLast comments");
        $this->assertEquals(array($this->escape('pop') => array('groups' => 1, 'comments' => -1)), $query->debug('newObj'));
    }

    public function testAddToSet()
    {
        $query = $this->parser->parse("update Documents\User addToSet groups = :group", array(':group' => 1));
        $this->assertEquals(array($this->escape('addToSet') => array('groups' => 1)), $query->debug('newObj'));
    }

    public function testAddManyToSet()
    {
        $query = $this->parser->parse("update Documents\User addManyToSet groups = :groups", array(':groups' => array(1, 2, 3)));
        $this->assertEquals(array($this->escape('addToSet') => array('groups' => array($this->escape('each') => array(1, 2, 3)))), $query->debug('newObj'));
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
        $this->assertEquals(array('count' => array($this->escape('gt') => 1)), $query->debug('where'));
    }

    public function testGreaterThanOrEqualTo()
    {
        $query = $this->parser->parse('find username Documents\User where count >= 1');
        $this->assertEquals(array('count' => array($this->escape('gte') => 1)), $query->debug('where'));
    }

    public function testLessThan()
    {
        $query = $this->parser->parse('find username Documents\User where count < 1');
        $this->assertEquals(array('count' => array($this->escape('lt') => 1)), $query->debug('where'));
    }

    public function testLessThanOrEqualTo()
    {
        $query = $this->parser->parse('find username Documents\User where count <= 1');
        $this->assertEquals(array('count' => array($this->escape('lte') => 1)), $query->debug('where'));
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
        $this->assertEquals(array('groups' => array($this->escape('in') => array(1, 2, 3))), $query->debug('where'));
    }

    public function testWhereNotIn()
    {
        $query = $this->parser->parse('find all Documents\User where groups notIn :groups', array(':groups' => array(1, 2, 3)));
        $this->assertEquals(array('groups' => array($this->escape('nin') => array(1, 2, 3))), $query->debug('where'));
    }

    public function testWhereAll()
    {
        $query = $this->parser->parse('find all Documents\User where groups all :groups', array(':groups' => array(1, 2, 3)));
        $this->assertEquals(array('groups' => array($this->escape('all') => array(1, 2, 3))), $query->debug('where'));
    }

    public function testWhereSize()
    {
        $query = $this->parser->parse('find all Documents\User where groups size 3');
        $this->assertEquals(array('groups' => array($this->escape('size') => 3)), $query->debug('where'));
    }

    public function testWhereExists()
    {
        $query = $this->parser->parse('find all Documents\User where groups exists true and comments exists false');
        $this->assertEquals(array('groups' => array($this->escape('exists') => true), 'comments' => array($this->escape('exists') => false)), $query->debug('where'));
    }

    public function testWhereType()
    {
        $query = $this->parser->parse('find all Documents\User where username type string');
        $this->assertEquals(array('username' => array($this->escape('type') => 2)), $query->debug('where'));
    }
}