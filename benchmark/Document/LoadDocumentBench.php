<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Benchmark\Document;

use DateTimeImmutable;
use Doctrine\ODM\MongoDB\Benchmark\BaseBench;
use Documents\Account;
use Documents\Address;
use Documents\Group;
use Documents\Phonenumber;
use Documents\User;
use MongoDB\BSON\ObjectId;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;

use function assert;

/**
 * @BeforeMethods({"init"}, extend=true)
 */
final class LoadDocumentBench extends BaseBench
{
    /** @var ObjectId */
    private static $userId;

    public function init()
    {
        self::$userId = new ObjectId();

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
        $this->loadDocument()->getPhonenumbers()->forAll(static function ($key, Phonenumber $element) {
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
        $this->loadDocument()->getGroups()->forAll(static function ($key, Group $group) {
            return $group->getName();
        });
    }

    /**
     * @return User
     */
    private function loadDocument()
    {
        $document = $this->getDocumentManager()->find(User::class, self::$userId);
        assert($document instanceof User);

        return $document;
    }
}
