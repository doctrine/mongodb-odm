<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

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

        $query = $this->dm->createQuery('Documents\SpecialUser')
            ->field('id')
            ->equals($user->getId());
        $user = $query->getSingleResult();
  
        $user->getProfile()->setLastName('Wage');
        $this->dm->flush();
        $this->dm->clear();
        
        $user = $query->getSingleResult();
        $this->assertEquals('Wage', $user->getProfile()->getLastName());
        $this->assertTrue($user instanceof \Documents\SpecialUser);
    }

    public function testSingleCollectionInhertiance()
    {
        $project = new \Documents\Project('Project');
        $this->dm->persist($project);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection('Documents\Project');
        $document = $coll->findOne(array('name' => 'Project'));
        $this->assertEquals('project', $document['type']);

        $subProject = new \Documents\SubProject('Sub Project');
        $this->dm->persist($subProject);
        $this->dm->flush();

        $coll = $this->dm->getDocumentCollection('Documents\SubProject');
        $document = $coll->findOne(array('name' => 'Sub Project'));
        $this->assertEquals('sub-project', $document['type']);

        $this->dm->clear();

        $document = $this->dm->findOne('Documents\Project', array('name' => 'Project'));
        $this->assertInstanceOf('Documents\Project', $document);

        $document = $this->dm->findOne('Documents\Project', array('name' => 'Project'));
        $this->assertInstanceOf('Documents\Project', $document);

        $document = $this->dm->findOne('Documents\SubProject', array('name' => 'Sub Project'));
        $this->assertInstanceOf('Documents\SubProject', $document);

        $document = $this->dm->findOne('Documents\SubProject', array('name' => 'Sub Project'));
        $this->assertInstanceOf('Documents\SubProject', $document);

        $document = $this->dm->findOne('Documents\Project', array('name' => 'Sub Project'));
        $this->assertInstanceOf('Documents\SubProject', $document);
        $this->dm->clear();

        $id = $document->getId();
        $document = $this->dm->find('Documents\Project', $id);
        $this->assertInstanceOf('Documents\SubProject', $document);
    }

    public function testPrePersistIsCalledFromMappedSuperClass()
    {
        $user = new \Documents\User();
        $user->setUsername('test');
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertTrue($user->persisted);
    }
}