<?php

namespace Documents;

/** @Document(collection="custom_users") */
class CustomUser
{
    /** @Id(strategy="none") */
    protected $id;

    /** @String */
    protected $username;

    /** @String */
    protected $password;

    /** @ReferenceOne(targetDocument="Account", cascade={"all"}) */
    protected $account;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function setAccount(Account $account)
    {
        $this->account = $account;
        $this->account->setUser($this);
    }

    public function getAccount()
    {
        return $this->account;
    }
}