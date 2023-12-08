<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document(collection: 'agents')]
class Agent
{
    /** @var string|null */
    #[ODM\Id]
    public $id;

    /** @var Server|GuestServer|null */
    #[ODM\ReferenceOne(discriminatorMap: ['server' => Server::class, 'server_guest' => GuestServer::class])]
    public $server;
}
