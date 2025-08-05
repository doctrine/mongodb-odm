<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tools\Console\Command\Schema;

use ReflectionMethod;
use Symfony\Component\Console\Command\Command;

// Symfony 8
if ((new ReflectionMethod(Command::class, 'configure'))->hasReturnType()) {
    /** @internal */
    trait AbstractCommandCompatibility
    {
        protected function configure(): void
        {
            $this->configureCommonOptions();
        }
    }
} else {
    /** @internal */
    trait AbstractCommandCompatibility
    {
        /** @return void */
        protected function configure()
        {
            $this->configureCommonOptions();
        }
    }
}
