<?php

namespace Documents;

/** * @Document(db="doctrine_odm_tests", collection="special_users") */
class SpecialUser extends User
{
    /** @Field */
    private $rules = array();

    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }

    public function getRules()
    {
        return $this->rules;
    }
}