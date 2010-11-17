<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\Address,
    Documents\Profile,
    Documents\Phonenumber,
    Documents\Account,
    Documents\Group,
    Documents\User,
    Documents\Functional\EmbeddedTestLevel0,
    Documents\Functional\EmbeddedTestLevel0b,
    Documents\Functional\EmbeddedTestLevel1,
    Documents\Functional\EmbeddedTestLevel2,
    Doctrine\ODM\MongoDB\PersistentCollection;

class EmbeddedTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testFlushEmbedded()
    {
        $test = new EmbeddedTestLevel0();
        $test->name = 'test';

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->findOne('Documents\Functional\EmbeddedTestLevel0', array('name' => 'test'));
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel0', $test);

        // Adding this flush here makes level1 not to be inserted.
        $this->dm->flush();

        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'test level1 #1';
        $test->level1[] = $level1;

        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->findOne('Documents\Functional\EmbeddedTestLevel0');
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel0', $test);
        $this->assertInstanceOf('Documents\Functional\EmbeddedTestLevel1', $test->level1[0]);

        $test->level1[0]->name = 'changed';
        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'testing';
        $test->level1->add($level1);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->findOne('Documents\Functional\EmbeddedTestLevel0');
        $this->assertEquals(2, count($test->level1));
        $this->assertEquals('changed', $test->level1[0]->name);
        $this->assertEquals('testing', $test->level1[1]->name);

        unset($test->level1[0]);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertEquals(1, count($test->level1));
    }

    public function testOneEmbedded()
    {
        $address = new Address();
        $address->setAddress('6512 Mercomatic Ct.');
        $address->setCity('Nashville');
        $address->setState('TN');
        $address->setZipcode('37209');

        $addressClone = clone $address;

        $user = new User();
        $user->setUsername('jwage');

        $this->dm->persist($user);
        $this->dm->flush();

        $user->setAddress($address);

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->createQuery('Documents\User')
            ->field('id')->equals($user->getId())
            ->getSingleResult();
        $this->assertEquals($addressClone, $user->getAddress());
    }

    public function testRemoveOneEmbedded()
    {
        $address = new Address();
        $address->setAddress('6512 Mercomatic Ct.');

        $user = new User();
        $user->setUsername('jwage');
        $user->setAddress($address);

        $this->dm->persist($user);
        $this->dm->flush();

        $user->removeAddress();
        $this->assertNull($user->getAddress());

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->createQuery('Documents\User')
            ->field('id')->equals($user->getId())
            ->getSingleResult();
        $this->assertNull($user->getAddress());
    }

    public function testManyEmbedded()
    {
        $user = new \Documents\User();
        $user->addPhonenumber(new Phonenumber('6155139185'));
        $user->addPhonenumber(new Phonenumber('6153303769'));

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user2 = $this->dm->createQuery('Documents\User')
            ->field('id')->equals($user->getId())
            ->getSingleResult();

        $this->assertEquals($user->getPhonenumbers()->unwrap(), $user2->getPhonenumbers()->unwrap());
    }

    public function testPostRemoveEventOnEmbeddedManyDocument()
    {
        // create a test document
        $test = new EmbeddedTestLevel0b();
        $test->name = 'embedded test';

        // embed one level1 in test
        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'test level1 #1';
        $test->level1[] = $level1;

        // persist test
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        // retrieve test
        $test = $this->dm->createQuery(get_class($test))
            ->field('id')->equals($test->id)
            ->getSingleResult();
        $level1 = $test->level1[0];

        // $test->level1[0] is available
        $this->assertEquals('test level1 #1', $level1->name);

        // remove all level1 from test
        $test->level1->clear();
        $this->dm->flush();

        // verify that level1 lifecycle callbacks have been called
        $this->assertTrue($level1->preRemove, 'the removed embedded document executed the PreRemove lifecycle callback');
        $this->assertTrue($level1->postRemove, 'the removed embedded document executed the PostRemove lifecycle callback');
    }

    public function testPostRemoveEventOnDeepEmbeddedManyDocument()
    {
        // create a test document
        $test = new EmbeddedTestLevel0b();
        $test->name = 'embedded test';

        // embed one level1 in test
        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'test level1 #1';
        $test->oneLevel1 = $level1;

        // embed one level2 in level1
        $level2 = new EmbeddedTestLevel2();
        $level2->name = 'test level2 #1';
        $level1->level2[] = $level2;

        // persist test
        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        // retrieve test
        $test = $this->dm->createQuery(get_class($test))
            ->field('id')->equals($test->id)
            ->getSingleResult();
        $level1 = $test->oneLevel1;
        $level2 = $level1->level2[0];

        // $test->oneLevel1->level2[0] is available
        $this->assertEquals('test level2 #1', $level2->name);

        // remove all level2 from level1
        $level1->level2->clear();
        $this->dm->flush();

        // verify that level2 lifecycle callbacks have been called
        $this->assertTrue($level2->preRemove, 'the removed embedded document executed the PreRemove lifecycle callback');
        $this->assertTrue($level2->postRemove, 'the removed embedded document executed the PostRemove lifecycle callback');
    }

    public function testEmbeddedLoadEvents()
    {
        // create a test document
        $test = new EmbeddedTestLevel0b();
        $test->name = 'embedded test';

        $level1 = new EmbeddedTestLevel1();
        $level1->name = 'test level1 #1';
        $test->oneLevel1 = $level1;

        $level2 = new EmbeddedTestLevel2();
        $level2->name = 'test level2 #1';
        $level1->level2[] = $level2;

        $this->dm->persist($test);
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->createQuery(get_class($test))
            ->field('id')->equals($test->id)
            ->getSingleResult();
        $level1 = $test->oneLevel1;
        $level2 = $level1->level2[0];

        $this->assertTrue($level1->preLoad);
        $this->assertTrue($level1->postLoad);
        $this->assertTrue($level2->preLoad);
        $this->assertTrue($level2->postLoad);
    }

    public function testEmbeddedDocumentChangesParent()
    {
        $address = new Address();
        $address->setAddress('6512 Mercomatic Ct.');
        $user = new User();
        $user->setUsername('jwagettt');
        $user->setAddress($address);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->findOne('Documents\User');
        $address = $user->getAddress();
        $address->setAddress('changed');

        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->findOne('Documents\User');
        $this->assertEquals('changed', $user->getAddress()->getAddress());
    }
}