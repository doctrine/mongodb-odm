<?php

namespace Doctrine\ODM\MongoDB\Tests\Functional;

use Doctrine\ODM\MongoDB\Tests\QueryLogger;
use Documents\Phonebook;
use Documents\Phonenumber;
use Documents\User;

class CommitImprovementTest extends \Doctrine\ODM\MongoDB\Tests\BaseTest
{
    /**
     * @var Doctrine\ODM\MongoDB\Tests\QueryLogger
     */
    private $ql;

    protected function getConfiguration()
    {
        if ( ! isset($this->ql)) {
            $this->ql = new QueryLogger();
        }

        $config = parent::getConfiguration();
        $config->setLoggerCallable($this->ql);

        return $config;
    }

    public function testInsertIncludesAllNestedCollections()
    {
        $user = new User();
        $user->setUsername('malarzm');
        $privateBook = new Phonebook('Private');
        $privateBook->addPhonenumber(new Phonenumber('12345678'));
        $user->addPhonebook($privateBook);
        $this->dm->persist($user);
        $this->dm->flush();
        $this->assertCount(1, $this->ql, 'Inserting a document includes all nested collections and requires one query');
        $this->dm->clear();

        $user = $this->dm->find(get_class($user), $user->getId());
        $this->assertEquals('malarzm', $user->getUsername());
        $this->assertCount(1, $user->getPhonebooks());
        $this->assertEquals('Private', $user->getPhonebooks()->first()->getTitle());
        $this->assertCount(1, $user->getPhonebooks()->first()->getPhonenumbers());
        $this->assertEquals('12345678', $user->getPhonebooks()->first()->getPhonenumbers()->first()->getPhonenumber());
    }
}
