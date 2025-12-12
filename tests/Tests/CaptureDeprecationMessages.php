<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Tests;

use function call_user_func;
use function restore_error_handler;
use function set_error_handler;

use const E_USER_DEPRECATED;

trait CaptureDeprecationMessages
{
    /**
     * This method can be replaced with expectUserDeprecationMessage() in PHPUnit 11+.
     * https://docs.phpunit.de/en/11.1/error-handling.html#expecting-deprecations-e-user-deprecated
     *
     * @param callable(): T $callable
     * @param list<string>  $errors
     *
     * @return T
     *
     * @param-out list<string> $errors
     * @template T
     */
    private function captureDeprecationMessages(callable $callable, ?array &$errors): mixed
    {
        $errors = [];

        set_error_handler(static function (int $errno, string $errstr) use (&$errors): bool {
            $errors[] = $errstr;

            return false;
        }, E_USER_DEPRECATED);

        try {
            return call_user_func($callable);
        } finally {
            restore_error_handler();
        }
    }
}
