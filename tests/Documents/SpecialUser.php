<?php

declare(strict_types=1);

namespace Documents;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

#[ODM\Document]
class SpecialUser extends User
{
    /** @var string[] */
    #[ODM\Field(type: 'collection')]
    private $rules = [];

    /** @param string[] $rules */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
    }

    /** @return string[] */
    public function getRules(): array
    {
        return $this->rules;
    }
}
