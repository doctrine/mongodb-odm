<?php

namespace Documents;

/** @Document(collection="agents") */
class Agent
{
    /** @Id */
    public $id;

    /**
     * @ReferenceOne(discriminatorMap={
     * "server"="Server",
     * "server_guest"="GuestServer"
     * })
     */
    public $server;
}