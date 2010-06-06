<?php

namespace Documents;

/** * @Document(customId=true) */
class CustomUser extends User
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
