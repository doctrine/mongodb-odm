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

        $qb = $this->dm->createQueryBuilder(SpecialUser::class)
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

        $coll = $this->dm->getDocumentCollection(SubProject::class);
        $document = $coll->findOne(['name' => 'Sub Project']);
        $this->assertEquals('sub-project', $document['type']);

        $project = new OtherSubProject('Other Sub Project');
        $this->dm->persist($project);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection(OtherSubProject::class);
        $document = $coll->findOne(['name' => 'Other Sub Project']);
        $this->assertEquals('other-sub-project', $document['type']);

        $this->dm->clear();

        $document = $this->dm->getRepository(SubProject::class)->findOneBy(['name' => 'Sub Project']);
        $this->assertInstanceOf(SubProject::class, $document);

        $document = $this->dm->getRepository(SubProject::class)->findOneBy(['name' => 'Sub Project']);
        $this->assertInstanceOf(SubProject::class, $document);

        $document = $this->dm->getRepository(Project::class)->findOneBy(['name' => 'Sub Project']);
        $this->assertInstanceOf(SubProject::class, $document);
        $this->dm->clear();

        $id = $document->getId();
        $document = $this->dm->find(Project::class, $id);
        $this->assertInstanceOf(SubProject::class, $document);

        $document = $this->dm->getRepository(Project::class)->findOneBy(['name' => 'Other Sub Project']);
        $this->assertInstanceOf(OtherSubProject::class, $document);
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

        $developer = $this->dm->find(Developer::class, $developer->getId());
        $projects  = $developer->getProjects();

        $this->assertEquals(3, $projects->count());

        $this->assertInstanceOf(Project::class, $projects[0]);
        $this->assertInstanceOf(SubProject::class, $projects[1]);
        $this->assertInstanceOf(OtherSubProject::class, $projects[2]);
    }
}
