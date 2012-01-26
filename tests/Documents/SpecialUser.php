<?php

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/** * @ODM\Document(db="doctrine_odm_tests", collection="special_users") */
class SpecialUser extends User
{
    /** @ODM\Field(type="collection") */
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