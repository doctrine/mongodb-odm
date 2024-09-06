<?php

declare(strict_types=1);

namespace Documentation\Introduction;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class IntroductionTest extends BaseTestCase
{
    public function testIntroduction(): void
    {
        // Insert
        $employee          = new Employee();
        $employee->name    = 'Employee';
        $employee->salary  = 50000;
        $employee->started = new DateTimeImmutable();
        $employee->address = new Address(
            address: '555 Doctrine Rd.',
            city: 'Nashville',
            state: 'TN',
            zipcode: '37209',
        );

        $project          = new Project('New Project');
        $manager          = new Manager();
        $manager->name    = 'Manager';
        $manager->salary  = 100_000;
        $manager->started = new DateTimeImmutable();
        $manager->projects->add($project);

        $this->dm->persist($employee);
        $this->dm->persist($project);
        $this->dm->persist($manager);
        $this->dm->flush();
        $this->dm->clear();

        $employee = $this->dm->find(Employee::class, $employee->id);
        $this->assertInstanceOf(Address::class, $employee->address);

        $manager = $this->dm->find(Manager::class, $manager->id);
        $this->assertInstanceOf(Project::class, $manager->projects[0]);

        // Update
        $newProject       = new Project('Another Project');
        $manager->salary  = 200_000;
        $manager->notes[] = 'Gave user 100k a year raise';
        $manager->changes++;
        $manager->projects->add($newProject);

        $this->dm->persist($newProject);
        $this->dm->flush();
        $this->dm->clear();

        $manager = $this->dm->find(Manager::class, $manager->id);
        $this->assertSame(200_000, $manager->salary);
        $this->assertCount(1, $manager->notes);
        $this->assertSame(1, $manager->changes);
        $this->assertCount(2, $manager->projects);
    }
}
