<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

class InheritanceTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testCollectionPerClassInheritance()
    {
        $profile = new \Documents\Profile();
        $profile->setFirstName('Jon');

        $user = new \Documents\SpecialUser();
        $user->setUsername('specialuser');
        $user->setProfile($profile);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertTrue($user->getId() !== '');
        $this->assertTrue($user->getProfile()->getProfileId() !== '');

        $qb = $this->dm->createQueryBuilder('Documents\SpecialUser')
            ->field('id')
            ->equals($user->getId());
        $query = $qb->getQuery();
        $user = $query->getSingleResult();

        $user->getProfile()->setLastName('Wage');
        $this->dm->flush();
        $this->dm->clear();

        $query = $qb->getQuery();
        $user = $query->getSingleResult();
        $this->assertEquals('Wage', $user->getProfile()->getLastName());
        $this->assertTrue($user instanceof \Documents\SpecialUser);
    }

    public function testSingleCollectionInhertiance()
    {
        $subProject = new \Documents\SubProject('Sub Project');
        $this->dm->persist($subProject);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection('Documents\SubProject');
        $document = $coll->findOne(array('name' => 'Sub Project'));
        $this->assertEquals('sub-project', $document['type']);

        $project = new \Documents\OtherSubProject('Other Sub Project');
        $this->dm->persist($project);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection('Documents\OtherSubProject');
        $document = $coll->findOne(array('name' => 'Other Sub Project'));
        $this->assertEquals('other-sub-project', $document['type']);

        $this->dm->clear();

        $document = $this->dm->getRepository('Documents\SubProject')->findOneBy(array('name' => 'Sub Project'));
        $this->assertInstanceOf('Documents\SubProject', $document);

        $document = $this->dm->getRepository('Documents\SubProject')->findOneBy(array('name' => 'Sub Project'));
        $this->assertInstanceOf('Documents\SubProject', $document);

        $document = $this->dm->getRepository('Documents\Project')->findOneBy(array('name' => 'Sub Project'));
        $this->assertInstanceOf('Documents\SubProject', $document);
        $this->dm->clear();

        $id = $document->getId();
        $document = $this->dm->find('Documents\Project', $id);
        $this->assertInstanceOf('Documents\SubProject', $document);

        $document = $this->dm->getRepository('Documents\Project')->findOneBy(array('name' => 'Other Sub Project'));
        $this->assertInstanceOf('Documents\OtherSubProject', $document);
    }

    public function testPrePersistIsCalledFromMappedSuperClass()
    {
        $user = new \Documents\User();
        $user->setUsername('test');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertTrue($user->persisted);
    }

    public function testInheritanceProxy()
    {
        $developer = new \Documents\Developer('avalanche123');

        $projects = $developer->getProjects();

        $projects->add(new \Documents\Project('Main Project'));
        $projects->add(new \Documents\SubProject('Sub Project'));
        $projects->add(new \Documents\OtherSubProject('Another Sub Project'));

        $this->dm->persist($developer);
        $this->dm->flush();
        $this->dm->clear();

        $developer = $this->dm->find('Documents\Developer', $developer->getId());
        $projects  = $developer->getProjects();

        $this->assertEquals(3, $projects->count());

        $this->assertInstanceOf('Documents\Project', $projects[0]);
        $this->assertInstanceOf('Documents\SubProject', $projects[1]);
        $this->assertInstanceOf('Documents\OtherSubProject', $projects[2]);
    }
}