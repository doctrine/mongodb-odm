<?php

namespace Doctrine\ODM\MongoDB\Benchmark\Document;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Documents\Account;
use Documents\Address;
use Documents\Group;
use Documents\Phonenumber;
use Documents\User;
use MongoId;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;

/**
 * @BeforeMethods({"init"}, extend=true)
 */
final class LoadDocumentBench extends BaseBench
{
    private static $userId;

    public function init()
    {
        self::$userId = new MongoId();

        $account = new Account();
        $account->setName('alcaeus');

        $address = new Address();
        $address->setAddress('Redacted');
        $address->setCity('Munich');

        $group1 = new Group('One');
        $group2 = new Group('Two');

        $user = new User();
        $user->setId(self::$userId);
        $user->setUsername('alcaeus');
        $user->setCreatedAt(new DateTimeImmutable());
        $user->setAddress($address);
        $user->setAccount($account);
        $user->addPhonenumber(new Phonenumber('12345678'));
        $user->addGroup($group1);
        $user->addGroup($group2);

        $this->getDocumentManager()->persist($user);
        $this->getDocumentManager()->flush();

        $this->getDocumentManager()->clear();
    }

    /**
     * @Warmup(2)
     */
    public function benchLoadDocument()
    {
        $this->loadDocument();
    }

    /**
     * @Warmup(2)
     */
    public function benchLoadEmbedOne()
    {
        $this->loadDocument()->getAddress()->getCity();
    }

    /**
     * @Warmup(2)
     */
    public function benchLoadEmbedMany()
    {
        $this->loadDocument()->getPhonenumbers()->forAll(function ($key, Phonenumber $element) {
            return $element->getPhoneNumber();
        });
    }

    /**
     * @Warmup(2)
     */
    public function benchLoadReferenceOne()
    {
        $this->loadDocument()->getAccount()->getName();
    }

    /**
     * @Warmup(2)
     */
    public function benchLoadReferenceMany()
    {
        $this->loadDocument()->getGroups()->forAll(function ($key, Group $group) {
            return $group->getName();
        });
    }

    /**
     * @return User
     */
    private function loadDocument()
    {
        return $this->getDocumentManager()->find(User::class, self::$userId);
    }
}
