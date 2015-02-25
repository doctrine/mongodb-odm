<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\CmsUser;
use Documents\CmsGroup;
use Documents\Developer;
use Documents\Project;

class PersistentCollectionClearSnapshotTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    private $project;

    private $developer;

    public function setUp()
    {
        parent::setUp();

        $project = new Project('project x');

        $developer = new Developer('Martha Facka');
        $developer->getProjects()->add($project);

        $this->dm->persist($project);
        $this->dm->persist($developer);
        $this->dm->flush();
        $this->dm->clear();

        $this->project = $this->dm->find(get_class($project), $project->getId());
        $this->developer = $this->dm->find(get_class($developer), $developer->getId());
    }

    public function testPersistentCollectionClearTakeSnapshot()
    {
        $this->assertFalse($this->developer->getProjects()->isCleared());

        $this->developer->getProjects()->clear();

        $this->assertTrue($this->developer->getProjects()->isCleared());
        $this->assertTrue($this->developer->getProjects()->isInitialized());
        $this->assertSame($this->developer->getProjects()->getSnapshot()[0], $this->project);
    }
}
