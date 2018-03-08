<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\BaseTest;
use Documents\Developer;
use Documents\OtherSubProject;
use Documents\Profile;
use Documents\Project;
use Documents\SpecialUser;
use Documents\SubProject;
use Documents\User;

class InheritanceTest extends BaseTest
{
    public function testCollectionPerClassInheritance()
    {
        $profile = new Profile();
        $profile->setFirstName('Jon');

        $user = new SpecialUser();
        $user->setUsername('specialuser');
        $user->setProfile($profile);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $this->assertNotSame('', $user->getId());
        $this->assertNotSame('', $user->getProfile()->getProfileId());

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
        $this->assertInstanceOf(SpecialUser::class, $user);
    }

    public function testSingleCollectionInhertiance()
    {
        $subProject = new SubProject('Sub Project');
        $this->dm->persist($subProject);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection('Documents\SubProject');
        $document = $coll->findOne(['name' => 'Sub Project']);
        $this->assertEquals('sub-project', $document['type']);

        $project = new OtherSubProject('Other Sub Project');
        $this->dm->persist($project);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection('Documents\OtherSubProject');
        $document = $coll->findOne(['name' => 'Other Sub Project']);
        $this->assertEquals('other-sub-project', $document['type']);

        $this->dm->clear();

        $document = $this->dm->getRepository('Documents\SubProject')->findOneBy(['name' => 'Sub Project']);
        $this->assertInstanceOf('Documents\SubProject', $document);

        $document = $this->dm->getRepository('Documents\SubProject')->findOneBy(['name' => 'Sub Project']);
        $this->assertInstanceOf('Documents\SubProject', $document);

        $document = $this->dm->getRepository('Documents\Project')->findOneBy(['name' => 'Sub Project']);
        $this->assertInstanceOf('Documents\SubProject', $document);
        $this->dm->clear();

        $id = $document->getId();
        $document = $this->dm->find('Documents\Project', $id);
        $this->assertInstanceOf('Documents\SubProject', $document);

        $document = $this->dm->getRepository('Documents\Project')->findOneBy(['name' => 'Other Sub Project']);
        $this->assertInstanceOf('Documents\OtherSubProject', $document);
    }

    public function testPrePersistIsCalledFromMappedSuperClass()
    {
        $user = new User();
        $user->setUsername('test');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertTrue($user->persisted);
    }

    public function testInheritanceProxy()
    {
        $developer = new Developer('avalanche123');

        $projects = $developer->getProjects();

        $projects->add(new Project('Main Project'));
        $projects->add(new SubProject('Sub Project'));
        $projects->add(new OtherSubProject('Another Sub Project'));

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
