<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\CmsUser;
use Documents\CmsGroup;
use Documents\Developer;
use Documents\Project;

class PersistentCollectionClearSnapshotTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private $project;

    private $projectTwo;

    private $developer;

    public function setUp()
    {
        parent::setUp();

        $project = new Project('project x');
        $project2 = new Project('secondProject');

        $developer = new Developer('Martha Facka');
        $developer->getProjects()->add($project);

        $this->dm->persist($project);
        $this->dm->persist($developer);
        $this->dm->persist($project2);
        $this->dm->flush();
        $this->dm->clear();

        $this->project = $this->dm->find(get_class($project), $project->getId());
        $this->projectTwo = $this->dm->find(get_class($project2), $project2->getId());
        $this->developer = $this->dm->find(get_class($developer), $developer->getId());
    }

    public function testPersistentCollectionClearTakeSnapshot()
    {
        $this->assertFalse($this->developer->getProjects()->isCleared());

        $this->developer->getProjects()->clear();

        $this->assertTrue($this->developer->getProjects()->isCleared());
        $this->assertTrue($this->developer->getProjects()->isInitialized());
        $this->assertSame($this->developer->getProjects()->getSnapshot()[0], $this->project);
        $this->assertSame($this->developer->getProjects()->getDeleteDiff()[0], $this->project);

        $this->developer->getProjects()->add($this->projectTwo);

        $this->assertNotNull($this->developer->getProjects()->getInsertDiff());
        $this->assertSame($this->developer->getProjects()->getInsertDiff()[0], $this->projectTwo);
    }
}
