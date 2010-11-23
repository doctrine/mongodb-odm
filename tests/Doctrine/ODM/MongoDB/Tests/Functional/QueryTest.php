<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Article,
    Documents\Account,
    Documents\Address,
    Documents\Group,
    Documents\Phonenumber,
    Documents\Profile,
    Documents\File,
    Documents\User;

class QueryTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $this->user = new User();
        $this->user->setUsername('boo');

        $this->dm->persist($this->user);
        $this->dm->flush();
    }

    public function testPrepareWhereValue()
    {
        $q = $this->dm->createQuery('Documents\User')
            ->field('profile.profileId')->equals('4ce6a8cdfdc212f420500100');
        $this->assertEquals(array('profile.$id' => new \MongoId('4ce6a8cdfdc212f420500100')), $q->debug('query'));

        $q = $this->dm->createQuery('Documents\User')
            ->field('profile.$id')->equals('4ce6a8cdfdc212f420500100');
        $this->assertEquals(array('profile.$id' => new \MongoId('4ce6a8cdfdc212f420500100')), $q->debug('query'));

        $q = $this->dm->createQuery('Documents\User')
            ->field('id')->equals('4ce6a8cdfdc212f420500100');
        $this->assertEquals(array('_id' => new \MongoId('4ce6a8cdfdc212f420500100')), $q->debug('query'));
    }

    public function testPrepareWhereValueWithCustomId()
    {
        $q = $this->dm->createQuery('Documents\CustomUser')
            ->field('id')->equals('4ce6a8cdfdc212f420500100');
        $this->assertEquals(array('_id' => '4ce6a8cdfdc212f420500100'), $q->debug('query'));

        $q = $this->dm->createQuery('Documents\CustomUser')
            ->field('account.$id')->equals('4ce6a8cdfdc212f420500100');
        $this->assertEquals(array('account.$id' => new \MongoId('4ce6a8cdfdc212f420500100')), $q->debug('query'));
    }

    public function testDistinct()
    {
        $user = new User();
        $user->setUsername('distinct_test');
        $user->setCount(1);
        $this->dm->persist($user);

        $user = new User();
        $user->setUsername('distinct_test');
        $user->setCount(1);
        $this->dm->persist($user);

        $user = new User();
        $user->setUsername('distinct_test');
        $user->setCount(2);
        $this->dm->persist($user);

        $user = new User();
        $user->setUsername('distinct_test');
        $user->setCount(3);
        $this->dm->persist($user);
        $this->dm->flush();

        $results = $this->dm->createQuery('Documents\User')
            ->distinct('count')
            ->field('username')->equals('distinct_test')
            ->execute();
        $this->assertEquals(new \Doctrine\ODM\MongoDB\MongoArrayIterator(array(1, 2, 3)), $results);

        $results = $this->dm->query('find distinct count from Documents\User WHERE username = ?', array('distinct_test'))
            ->execute();
        $this->assertEquals(new \Doctrine\ODM\MongoDB\MongoArrayIterator(array(1, 2, 3)), $results);
    }

    public function testFindQuery()
    {
        $query = $this->dm->createQuery('Documents\User')
            ->where("function() { return this.username == 'boo' }");
        $user = $query->getSingleResult();
        $this->assertEquals('boo', $user->getUsername());

        $query = $this->dm->createQuery('Documents\User')
            ->reduce("function() { return this.username == 'boo' }");
        $user = $query->getSingleResult();
        $this->assertEquals('boo', $user->getUsername());
    }

    public function testUpdateQuery()
    {
        $query = $this->dm->createQuery('Documents\User')
            ->update()
            ->field('username')
            ->set('crap')
            ->equals('boo');
        $result = $query->execute();

        $this->dm->refresh($this->user);
        $this->assertEquals('crap', $this->user->getUsername());
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testRemoveQuery()
    {
        $query = $this->dm->createQuery('Documents\User')
            ->remove()
            ->field('username')->equals('boo');
        $result = $query->execute();

        // should invoke exception because $this->user doesn't exist anymore
        $this->dm->refresh($this->user);
    }

    public function testIncUpdateQuery()
    {
        $query = $this->dm->createQuery('Documents\User')
            ->update()
            ->field('hits')->inc(5)
            ->field('username')->equals('boo');
        $query->execute();
        $query->execute();

        $user = $query->find('Documents\User')
            ->hydrate(false)
            ->getSingleResult();
        $this->assertEquals(10, $user['hits']);
    }

    public function testUnsetFieldUpdateQuery()
    {
        $query = $this->dm->createQuery('Documents\User')
            ->update()
            ->field('hits')->unsetField()
            ->field('username')->equals('boo');
        $result = $query->execute();

        $user = $query->find('Documents\User')
            ->hydrate(false)
            ->getSingleResult();
        $this->assertFalse(isset($user['hits']));
    }

    public function testGroup()
    {
        $query = $this->dm->createQuery('Documents\User')
            ->group(array(), array('count' => 0))
            ->reduce('function (obj, prev) { prev.count++; }');
        $result = $query->execute();
        $this->assertEquals(1, $result['retval'][0]['count']);
    }

    public function testUnsetField()
    {
        $this->dm->createQuery()
            ->update('Documents\User')
            ->field('nullTest')
            ->type('null')
            ->unsetField('nullTest')
            ->execute();

        $user = $this->dm->createQuery('Documents\User')
            ->field('nullTest')->type('null')
            ->getSingleResult();
        $this->assertNull($user);
    }

    public function testDateRange()
    {
        $article1 = new Article();
        $article1->setTitle('test');
        $article1->setBody('test');
        $article1->setCreatedAt('1985-09-01 00:00:00');

        $article2 = new Article();
        $article2->setTitle('test');
        $article2->setBody('test');
        $article2->setCreatedAt('1985-09-02 00:00:00');

        $article3 = new Article();
        $article3->setTitle('test');
        $article3->setBody('test');
        $article3->setCreatedAt('1985-09-03 00:00:00');

        $article4 = new Article();
        $article4->setTitle('test');
        $article4->setBody('test');
        $article4->setCreatedAt('1985-09-04 00:00:00');

        $this->dm->persist($article1);
        $this->dm->persist($article2);
        $this->dm->persist($article3);
        $this->dm->persist($article4);

        $this->dm->flush();
        $this->dm->clear();

        $query = $this->dm->createQuery('Documents\Article');
        $query->field('createdAt')->range(
            new \MongoDate(strtotime('1985-09-01 01:00:00')),
            new \MongoDate(strtotime('1985-09-04'))
        );

        $articles = array_values($query->execute()->getResults());
        $this->assertEquals(2, count($articles));
        $this->assertEquals('1985-09-02', $articles[0]->getCreatedAt()->format('Y-m-d'));
        $this->assertEquals('1985-09-03', $articles[1]->getCreatedAt()->format('Y-m-d'));
    }

    public function testQueryIsIterable()
    {
        $article = new Article();
        $article->setTitle('test');
        $this->dm->persist($article);
        $this->dm->flush(array('safe' => true));

        $query = $this->dm->createQuery('Documents\Article');
        $this->assertTrue($query instanceof \Doctrine\ODM\MongoDB\MongoIterator);
        foreach ($query as $article) {
            $this->assertEquals('Documents\Article', get_class($article));
        }
    }
}
