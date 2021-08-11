<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Annotations;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;
use TypeError;

class ValidationTest extends BaseTest
{
    public function testWrongTypeForValidationValidatorShouldThrowException(): void
    {
        $this->expectException(TypeError::class);
        $this->dm->getClassMetadata(WrongTypeForValidationValidator::class);
    }
}

/** @ODM\Validation(validator={"wrong"}) */
class WrongTypeForValidationValidator
{
}
