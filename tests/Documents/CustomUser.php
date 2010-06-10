<?php

namespace Documents;

/** * @Document */
class CustomUser extends User
{
    /** @Id(custom=true) */
    protected $id;

    public function setId($id)
    {
        $this->id = $id;
    }
}
