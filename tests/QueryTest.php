<?php

require_once 'TestInit.php';

use Documents\Account,
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
}