<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class MODM65Test extends BaseTest
{
    public function testTest(): void
    {
        $user                               = new MODM65User();
        $user->socialNetworkUser            = new MODM65SocialNetworkUser();
        $user->socialNetworkUser->firstName = 'Jonathan';
        $user->socialNetworkUser->lastName  = 'Wage';
        $this->dm->persist($user);
        $this->dm->flush();
        $this->dm->clear();

        $user = $this->dm->getDocumentCollection(MODM65User::class)->findOne();
        $this->assertTrue(isset($user['snu']['lN']));
        $this->assertTrue(isset($user['snu']['fN']));

        $user = $this->dm->find(MODM65User::class, $user['_id']);
        $this->assertEquals('Jonathan', $user->socialNetworkUser->firstName);
        $this->assertEquals('Wage', $user->socialNetworkUser->lastName);
    }
}

/**
 * @ODM\Document(collection="modm65_users")
 */
class MODM65User
{
    /** @ODM\Id */
    public $id;
    /**
     * @ODM\EmbedOne(
     *  discriminatorField="php",
     *  discriminatorMap={
     *      "fbu"=Doctrine\ODM\MongoDB\Tests\Functional\Ticket\MODM65SocialNetworkUser::class
     *  },
     *  name="snu"
     * )
     */
    public $socialNetworkUser;
}

/**
 * @ODM\EmbeddedDocument
 */
class MODM65SocialNetworkUser
{
    /**
     * @ODM\Field(name="fN", type="string")
     *
     * @var string
     */
    public $firstName;
    /**
     * @ODM\Field(name="lN", type="string")
     *
     * @var string
     */
    public $lastName;
}
