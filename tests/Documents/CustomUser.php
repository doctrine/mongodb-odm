<?php

namespace Documents;

/** @Document(collection="custom_users") */
class CustomUser extends User
{
    /** @Id(custom=true) */
    protected $id;

    public function setId($id)
    {
        $this->id = $id;
    }
}
