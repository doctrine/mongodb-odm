<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Proxy;

use Closure;
use InvalidArgumentException;

use function file_exists;
use function ltrim;
use function spl_autoload_register;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR;

/**
 * Special Autoloader for Proxy classes, which are not PSR-0 compliant.
 *
 * @internal
 */
final class Autoloader
{
    /**
     * Resolves proxy class name to a filename based on the following pattern.
     *
     * 1. Remove Proxy namespace from class name.
     * 2. Remove namespace separators from remaining class name.
     * 3. Return PHP filename from proxy-dir with the result from 2.
     *
     * @param class-string $className
     *
     * @throws InvalidArgumentException
     */
    public static function resolveFile(string $proxyDir, string $proxyNamespace, string $className): string
    {
        if (! str_starts_with($className, $proxyNamespace)) {
            throw new InvalidArgumentException(sprintf('The class %s is not a proxy class in namespace %s', $className, $proxyNamespace));
        }

        // remove proxy namespace from class name
        $classNameRelativeToProxyNamespace = substr($className, strlen($proxyNamespace));

        // remove namespace separators from remaining class name
        $fileName = str_replace('\\', '', $classNameRelativeToProxyNamespace);

        return $proxyDir . DIRECTORY_SEPARATOR . $fileName . '.php';
    }

    /**
     * Registers and returns autoloader callback for the given proxy dir and namespace.
     *
     * @param Closure(string, string, class-string): void|null $notFoundCallback Invoked when the proxy file is not found.
     *
     * @return Closure(string): void
     */
    public static function register(
        string $proxyDir,
        string $proxyNamespace,
        Closure|null $notFoundCallback = null,
    ): Closure {
        $proxyNamespace = ltrim($proxyNamespace, '\\');

        $autoloader = /** @param class-string $className */ static function (string $className) use ($proxyDir, $proxyNamespace, $notFoundCallback): void {
            if ($proxyNamespace === '') {
                return;
            }

            if (! str_starts_with($className, $proxyNamespace)) {
                return;
            }

            $file = Autoloader::resolveFile($proxyDir, $proxyNamespace, $className);

            if ($notFoundCallback && ! file_exists($file)) {
                $notFoundCallback($proxyDir, $proxyNamespace, $className);
            }

            require $file;
        };

        spl_autoload_register($autoloader);

        return $autoloader;
    }
}
