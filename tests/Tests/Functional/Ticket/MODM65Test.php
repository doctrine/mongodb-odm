<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;

class MODM65Test extends BaseTestCase
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
        self::assertTrue(isset($user['snu']['lN']));
        self::assertTrue(isset($user['snu']['fN']));

        $user = $this->dm->find(MODM65User::class, $user['_id']);
        self::assertEquals('Jonathan', $user->socialNetworkUser->firstName);
        self::assertEquals('Wage', $user->socialNetworkUser->lastName);
    }
}

#[ODM\Document(collection: 'modm65_users')]
class MODM65User
{
    /** @var string|null */
    #[ODM\Id]
    public $id;
    /** @var MODM65SocialNetworkUser|null */
    #[ODM\EmbedOne(discriminatorField: 'php', discriminatorMap: ['fbu' => MODM65SocialNetworkUser::class], name: 'snu')]
    public $socialNetworkUser;
}

#[ODM\EmbeddedDocument]
class MODM65SocialNetworkUser
{
    /** @var string */
    #[ODM\Field(name: 'fN', type: 'string')]
    public $firstName;
    /** @var string */
    #[ODM\Field(name: 'lN', type: 'string')]
    public $lastName;
}
