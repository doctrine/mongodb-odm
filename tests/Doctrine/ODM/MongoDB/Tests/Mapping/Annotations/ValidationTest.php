<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests\Mapping\Annotations;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;
use Doctrine\ODM\MongoDB\Tests\BaseTest;

class ValidationTest extends BaseTest
{
    public function testWrongTypeForValidationValidatorShouldThrowException()
    {
        $this->expectException(AnnotationException::class);
        $this->expectExceptionMessage('[Type Error] Attribute "validator" of @ODM\Validation declared on class Doctrine\ODM\MongoDB\Tests\Mapping\Annotations\WrongTypeForValidationValidator expects a(n) string, but got array.');
        $this->dm->getClassMetadata(WrongTypeForValidationValidator::class);
    }

    public function testWrongTypeForValidationActionShouldThrowException()
    {
        $this->expectException(AnnotationException::class);
        $this->expectExceptionMessage('[Type Error] Attribute "action" of @ODM\Validation declared on class Doctrine\ODM\MongoDB\Tests\Mapping\Annotations\WrongTypeForValidationAction expects a(n) string, but got boolean.');
        $this->dm->getClassMetadata(WrongTypeForValidationAction::class);
    }

    public function testWrongValueForValidationActionShouldThrowException()
    {
        $this->expectException(AnnotationException::class);
        $this->expectExceptionMessageMatches('#^\[Enum Error\] Attribute "action" of @Doctrine\\\\ODM\\\\MongoDB\\\\Mapping\\\\Annotations\\\\Validation declared on class Doctrine\\\\ODM\\\\MongoDB\\\\Tests\\\\Mapping\\\\Annotations\\\\WrongValueForValidationAction accepts? only \[error, warn\], but got wrong\.$#');
        $this->dm->getClassMetadata(WrongValueForValidationAction::class);
    }

    public function testWrongTypeForValidationLevelShouldThrowException()
    {
        $this->expectException(AnnotationException::class);
        $this->expectExceptionMessage('[Type Error] Attribute "level" of @ODM\Validation declared on class Doctrine\ODM\MongoDB\Tests\Mapping\Annotations\WrongTypeForValidationLevel expects a(n) string, but got boolean.');
        $this->dm->getClassMetadata(WrongTypeForValidationLevel::class);
    }

    public function testWrongValueForValidationLevelShouldThrowException()
    {
        $this->expectException(AnnotationException::class);
        $this->expectExceptionMessageMatches('#^\[Enum Error\] Attribute "level" of @Doctrine\\\\ODM\\\\MongoDB\\\\Mapping\\\\Annotations\\\\Validation declared on class Doctrine\\\\ODM\\\\MongoDB\\\\Tests\\\\Mapping\\\\Annotations\\\\WrongValueForValidationLevel accepts? only \[off, strict, moderate\], but got wrong\.$#');
        $this->dm->getClassMetadata(WrongValueForValidationLevel::class);
    }
}

/** @ODM\Validation(validator={"wrong"}) */
class WrongTypeForValidationValidator
{
}

/** @ODM\Validation(action=true) */
class WrongTypeForValidationAction
{
}

/** @ODM\Validation(action="wrong") */
class WrongValueForValidationAction
{
}

/** @ODM\Validation(level=true) */
class WrongTypeForValidationLevel
{
}

/** @ODM\Validation(level="wrong") */
class WrongValueForValidationLevel
{
}
