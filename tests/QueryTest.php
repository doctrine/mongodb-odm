<?php

require_once 'TestInit.php';

use Documents\Article,
    Documents\Account,
    Documents\Address,
    Documents\Group,
    Documents\Phonenumber,
    Documents\Profile,
    Documents\File,
    Documents\User;

class QueryTest extends BaseTest
{
    public function setUp()
    {
        parent::setUp();

        $this->user = new User();
        $this->user->setUsername('boo');

        $this->dm->persist($this->user);
        $this->dm->flush();
    }

    public function testFindQuery()
    {
        $query = $this->dm->createQuery('Documents\User')
            ->where('$where', "function() { return this.username == 'boo' }");
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
            ->where('username', 'boo')
            ->set('username', 'crap');
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
            ->where('username', 'boo');
        $result = $query->execute();

        // should invoke exception because $this->user doesn't exist anymore
        $this->dm->refresh($this->user);
    }

    public function testIncUpdateQuery()
    {
        $query = $this->dm->createQuery('Documents\User')
            ->update()
            ->inc('hits', 5)
            ->where('username', 'boo');
        $query->execute();
        $query->execute();

        $user = $query->from('Documents\User')
            ->hydrate(false)
            ->getSingleResult();
        $this->assertEquals(10, $user['hits']);
    }

    public function testUnsetFieldUpdateQuery()
    {
        $query = $this->dm->createQuery('Documents\User')
            ->update()
            ->unsetField('hits')
            ->where('username', 'boo');
        $result = $query->execute();

        $user = $query->from('Documents\User')
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
            ->whereType('nullTest', 'null')
            ->unsetField('nullTest')
            ->execute();

        $user = $this->dm->createQuery('Documents\User')
            ->whereType('nullTest', 'null')
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
        $query->whereRange('createdAt',
            new \MongoDate(strtotime('1985-09-01')),
            new \MongoDate(strtotime('1985-09-04'))
        );

        $articles = array_values($query->execute());
        $this->assertEquals(2, count($articles));
        $this->assertEquals('1985-09-02', $articles[0]->getCreatedAt()->format('Y-m-d'));
        $this->assertEquals('1985-09-03', $articles[1]->getCreatedAt()->format('Y-m-d'));
    }
}