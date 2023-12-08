<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Functional\Ticket;

use Doctrine\ODM\MongoDB\Event\PreLoadEventArgs;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTestCase;
use MongoDB\BSON\ObjectId;

use function explode;

class MODM43Test extends BaseTestCase
{
    public function testTest(): void
    {
        $person = [
            '_id' => new ObjectId(),
            'name' => 'Jonathan Wage',
        ];
        $this->dm->getDocumentCollection(Person::class)->insertOne($person);
        $user = $this->dm->find(Person::class, $person['_id']);
        self::assertEquals('Jonathan', $user->firstName);
        self::assertEquals('Wage', $user->lastName);
    }
}

#[ODM\Document]
#[ODM\HasLifecycleCallbacks]
class Person
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $firstName;

    /** @var string|null */
    #[ODM\Field(type: 'string')]
    public $lastName;

    #[ODM\PreLoad]
    public function preLoad(PreLoadEventArgs $e): void
    {
        $data =& $e->getData();
        if (! isset($data['name'])) {
            return;
        }

        $e                 = explode(' ', $data['name']);
        $data['firstName'] = $e[0];
        $data['lastName']  = $e[1];
    }
}
