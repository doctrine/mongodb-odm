<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

require_once __DIR__ . '/../../../../../TestInit.php';

use Documents\User,
    Documents\Phonenumber,
    Documents\Employee,
    Documents\Manager,
    Documents\Address,
    Documents\Project;

class FunctionalTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{

    public function tearDown()
    {
        parent::tearDown();
        $documents = array(
            'Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad',
            'Doctrine\ODM\MongoDB\Tests\Functional\NotAnnotatedDocument',
            'Doctrine\ODM\MongoDB\Tests\Functional\NotSaved',
            'Doctrine\ODM\MongoDB\Tests\Functional\NullFieldValues',
            'Doctrine\ODM\MongoDB\Tests\Functional\SimpleEmbedAndReference',
        );
        foreach ($documents as $document) {
            $this->dm->getDocumentCollection($document)->drop();
        }
    }
    public function testSameObjectValuesInCollection()
    {
        $user = new User();
        $user->setUsername('testing');
        $user->getPhonenumbers()->add(new Phonenumber('6155139185'));
        $user->getPhonenumbers()->add(new Phonenumber('6155139185'));
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->findOne('Documents\User', array('username' => 'testing'));
        $this->assertEquals(2, count($user->getPhonenumbers()));
    }

    public function testSearchEmbeddedDocumentDQL()
    {
        $user = new \Documents\User();
        $user->setUsername('jwage');
        $address = new \Documents\Address();
        $address->setCity('nashville');
        $user->setAddress($address);

        $user->addPhonenumber(new \Documents\Phonenumber('6155139185'));
        $this->dm->persist($user);
        $this->dm->flush();

        $this->assertNotNull($this->dm->find('Documents\User', array('phonenumbers.phonenumber' => '6155139185'))->getSingleResult());

        $query = $this->dm->query("find all Documents\User where phonenumbers.phonenumber = '6155139185'");
        $this->assertNotNull($query->getSingleResult());

        $query = $this->dm->query("find all Documents\User where phonenumbers.phonenumber = ?", array('6155139185'));
        $this->assertNotNull($query->getSingleResult());

        $query = $this->dm->query('find all Documents\User where address.city = ?', 'nashville');
        $this->assertNotNull($query->getSingleResult());

        $query = $this->dm->query('find all Documents\User where phonenumbers size :size', array(':size' => 1));
        $this->assertNotNull($query->getSingleResult());

        $query = $this->dm->query('find all Documents\User where phonenumbers size ?', 1);
        $this->assertNotNull($query->getSingleResult());

        $query = $this->dm->query('find all Documents\User where phonenumbers size 1');
        $this->assertNotNull($query->getSingleResult());

        $this->dm->query('update Documents\User set address.city = ?', 'atlanta')
            ->execute();

        $query = $this->dm->query('find all Documents\User where address.city = ?', 'atlanta');
        $this->assertNotNull($query->getSingleResult());

        $this->dm->query('remove Documents\User where address.city = ?', 'atlanta')
            ->execute();

        $query = $this->dm->query('find all Documents\User where address.city = ?', 'atlanta');
        $this->assertNull($query->getSingleResult());

        $this->dm->query("insert Documents\User set username = 'jwage', address.city = 'atlanta'")
            ->execute();
        $query = $this->dm->query('find all Documents\User where address.city = ? and username = ?', array('atlanta', 'jwage'));
        $this->assertNotNull($query->getSingleResult());
    }

    public function testFunctionalDQLQuery()
    {
        $user = new \Documents\User();
        $user->setUsername('jwage');
        $this->dm->persist($user);
        $this->dm->flush();

        $query = $this->dm->query("find all Documents\User where username = :username", array(':username' => 'jwage'));
        $this->assertNotNull($query->getSingleResult());
    }

    public function testIncrement()
    {
        $user = new User();
        $user->setUsername('jon');
        $user->setCount(100);

        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->findOne('Documents\User', array('username' => 'jon'));

        $user->incrementCount(5);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->findOne('Documents\User', array('username' => 'jon'));
        $this->assertEquals(105, $user->getCount());

        $user->setCount(50);

        $this->dm->flush();
        $this->dm->clear();
        $user = $this->dm->findOne('Documents\User', array('username' => 'jon'));
        $this->assertEquals(50, $user->getCount());
    }

    public function testTest()
    {
        $employee = new Employee();
        $employee->setName('Employee');
        $employee->setSalary(50000.00);
        $employee->setStarted(new \DateTime());

        $address = new Address();
        $address->setAddress('555 Doctrine Rd.');
        $address->setCity('Nashville');
        $address->setState('TN');
        $address->setZipcode('37209');
        $employee->setAddress($address);

        $project = new Project('New Project');
        $manager = new Manager();
        $manager->setName('Manager');
        $manager->setSalary(100000.00);
        $manager->setStarted(new \DateTime());
        $manager->addProject($project);

        $this->dm->persist($employee);
        $this->dm->persist($address);
        $this->dm->persist($project);
        $this->dm->persist($manager);
        $this->dm->flush();

        $newProject = new Project('Another Project');
        $manager->setSalary(200000.00);
        $manager->addNote('Gave user 100k a year raise');
        $manager->incrementChanges(2);
        $manager->addProject($newProject);

        $this->dm->persist($newProject);
        $this->dm->flush();
        $this->dm->clear();

        $results = $this->dm->find('Documents\Manager', array('name' => 'Manager'))
            ->hydrate(false)
            ->getResults();
        $result = current($results);

        $this->assertEquals(1, count($results));
        $this->assertEquals(200000.00, $result['salary']);
        $this->assertEquals(2, count($result['projects']));
        $this->assertEquals(1, count($result['notes']));
        $this->assertEquals('Gave user 100k a year raise', $result['notes'][0]);
    }

    public function testNotAnnotatedDocument()
    {
        $this->dm->getDocumentCollection('Doctrine\ODM\MongoDB\Tests\Functional\NotAnnotatedDocument')->drop();

        $test = new NotAnnotatedDocument();
        $test->field = 'test';
        $test->transientField = 'w00t';
        $this->dm->persist($test);
        $this->dm->flush($test);
        $this->dm->clear();

        $test = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\NotAnnotatedDocument')
            ->getSingleResult();
        $this->assertNotNull($test);
        $this->assertFalse(isset($test->transientField));
    }

    public function testNullFieldValuesAllowed()
    {
        $this->dm->getDocumentCollection('Doctrine\ODM\MongoDB\Tests\Functional\NullFieldValues')->drop();

        $test = new NullFieldValues();
        $test->field = null;
        $this->dm->persist($test);
        $this->dm->flush();

        $test = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\NullFieldValues')
            ->hydrate(false)
            ->getResults();
        $document = current($test);
        $this->assertNotNull($test);
        $this->assertNull($document['field']);

        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\NullFieldValues')
            ->getSingleResult();
        $document->field = 'test';
        $this->dm->flush();
        $this->dm->clear();

        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\NullFieldValues')
            ->getSingleResult();
        $this->assertEquals('test', $document->field);
        $document->field = null;
        $this->dm->flush();
        $this->dm->clear();

        $test = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\NullFieldValues')
            ->hydrate(false)
            ->getSingleResult();
        $this->assertNull($test['field']);
        $this->assertFalse(isset($test['transientField']));
    }

    public function testAlsoLoadOnProperty()
    {
        $collection = $this->dm->getDocumentCollection('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad');
        $collection->drop();
        $collection->insert(array(
            'bar' => 'w00t'
        ));
        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad', array('bar' => 'w00t'))
            ->getSingleResult();
        $this->assertEquals('w00t', $document->foo);

        $collection->insert(array(
            'foo' => 'cool'
        ));
        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad', array('bar' => 'w00t'))
            ->getSingleResult();
        $this->assertNotNull($document->foo);

        $collection->insert(array(
            'zip' => 'test'
        ));
        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad', array('bar' => 'w00t'))
            ->getSingleResult();
        $this->assertNotNull($document->foo);
    }

    public function testAlsoLoadOnMethod()
    {
        $collection = $this->dm->getDocumentCollection('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad');
        $collection->drop();
        $collection->insert(array(
            'name' => 'Jonathan Wage',
            'test1' => 'test1'
        ));
        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad', array('name' => 'Jonathan Wage'))
            ->getSingleResult();
        $this->assertEquals('Jonathan', $document->firstName);
        $this->assertEquals('Wage', $document->lastName);
        $this->assertEquals('test1', $document->test);

        $collection->insert(array(
            'fullName' => 'Jonathan Wage',
            'test2' => 'test2'
        ));
        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad', array('fullName' => 'Jonathan Wage'))
            ->getSingleResult();
        $this->assertEquals('Jonathan', $document->firstName);
        $this->assertEquals('Wage', $document->lastName);
        $this->assertEquals('test2', $document->test);

        $collection->insert(array(
            'test' => 'test'
        ));
        $document = $this->dm->find('Doctrine\ODM\MongoDB\Tests\Functional\AlsoLoad', array('test' => 'test'))
            ->getSingleResult();
        $this->assertEquals('test', $document->test);
    }

    public function testSimplerEmbedAndReference()
    {
        $class = $this->dm->getClassMetadata('Doctrine\ODM\MongoDB\Tests\Functional\SimpleEmbedAndReference');
        $this->assertEquals('many', $class->fieldMappings['embedMany']['type']);
        $this->assertEquals('one', $class->fieldMappings['embedOne']['type']);
        $this->assertEquals('many', $class->fieldMappings['referenceMany']['type']);
        $this->assertEquals('one', $class->fieldMappings['referenceOne']['type']);
    }

    public function testNotSavedFields()
    {
        $collection = $this->dm->getDocumentCollection('Doctrine\ODM\MongoDB\Tests\Functional\NotSaved');
        $collection->drop();
        $collection->insert(array(
            'name' => 'Jonathan Wage',
            'notSaved' => 'test'
        ));
        $notSaved = $this->dm->findOne('Doctrine\ODM\MongoDB\Tests\Functional\NotSaved');
        $this->assertEquals('Jonathan Wage', $notSaved->name);
        $this->assertEquals('test', $notSaved->notSaved);

        $notSaved = new NotSaved();
        $notSaved->name = 'Roman Borschel';
        $notSaved->notSaved = 'test';
        $this->dm->persist($notSaved);
        $this->dm->flush();
        $this->dm->clear();

        $notSaved = $collection->findOne(array('name' => 'Roman Borschel'));
        $this->assertEquals('Roman Borschel', $notSaved['name']);
        $this->assertFalse(isset($notSaved['notSaved']));
    }
}

/** @Document(collection="functional_tests") */
class NotSaved
{
    /** @Id */
    public $id;
    /** @String */
    public $name;

    /** @NotSaved */
    public $notSaved;
}

/** @Document(collection="functional_tests") */
class SimpleEmbedAndReference
{
    /** @Embed(targetDocument="Reference") */
    public $embedMany = array();

    /** @Reference(targetDocument="Embedded") */
    public $referenceMany = array();

    /** @Embed(targetDocument="Reference") */
    public $embedOne;

    /** @Reference(targetDocument="Embedded") */
    public $referenceOne;
}

/** @Document(collection="functional_tests") */
class AlsoLoad
{
    /**
     * @AlsoLoad({"bar", "zip"})
     */
    public $foo;

    /** @NotSaved */
    public $bar;

    /** @NotSaved */
    public $zip;

    /** @NotSaved */
    public $name;

    /** @NotSaved */
    public $fullName;

    /** @String */
    public $firstName;

    /** @String */
    public $lastName;

    /** @String */
    public $test;

    /** @String */
    public $test1;

    /** @String */
    public $test2;

    /** @AlsoLoad({"name", "fullName"}) */
    public function populateFirstAndLastName($name)
    {
        $e = explode(' ', $name);
        $this->firstName = $e[0];
        $this->lastName = $e[1];
    }

    /** @AlsoLoad({"test1", "test2"}) */
    public function populateTest($test)
    {
        $this->test = $test;
    }
}

/** @Document(collection="functional_tests") */
class NullFieldValues
{
    /** @Field(nullable=true) */
    public $field;
}

/** @Document(collection="functional_tests") */
class NotAnnotatedDocument
{
    /** @Field */
    public $field;

    public $transientField;
}