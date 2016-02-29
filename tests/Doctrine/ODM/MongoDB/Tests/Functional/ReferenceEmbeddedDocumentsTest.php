<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Documents\SubProject;
use Documents\Project;
use Documents\Issue;
use Doctrine\Common\Collections\ArrayCollection;

class ReferenceEmbeddedDocumentsTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    public function testSavesEmbeddedDocumentsInReferencedDocument()
    {
        $project = new Project('OpenSky');

        $this->dm->persist($project);
        $this->dm->flush();
        $this->dm->clear();

        $project = $this->dm->find('Documents\Project', $project->getId());

        $subProjects = new ArrayCollection();
        $subProject1 = new SubProject('Sub Project #1');
        $subProject2 = new SubProject('Sub Project #2');

        $subProject1->setIssues(new ArrayCollection(array(
            new Issue('Issue #1', 'Issue #1 on Sub Project #1'),
            new Issue('Issue #2', 'Issue #2 on Sub Project #1')
        )));

        $subProject2->setIssues(new ArrayCollection(array(
            new Issue('Issue #1', 'Issue #1 on Sub Project #2'),
            new Issue('Issue #2', 'Issue #2 on Sub Project #2')
        )));

        $subProjects->add($subProject1);
        $subProjects->add($subProject2);

        $project->setSubProjects($subProjects);

        $this->dm->flush();
        $this->dm->clear();

        $project = $this->dm->find('Documents\Project', $project->getId());

        $subProjects = $project->getSubProjects();

        $this->assertEquals(2, $subProjects->count());

        $this->assertFirstSubProject($subProjects->first());
        $this->assertLastSubProject($subProjects->last());
    }

    private function assertFirstSubProject(SubProject $project)
    {
        $this->assertEquals('Sub Project #1', $project->getName());
        $this->assertEquals(2, $project->getIssues()->count());
    }

    private function assertLastSubProject(SubProject $project)
    {
        $this->assertEquals('Sub Project #2', $project->getName());
        $this->assertEquals(2, $project->getIssues()->count());
    }
}
