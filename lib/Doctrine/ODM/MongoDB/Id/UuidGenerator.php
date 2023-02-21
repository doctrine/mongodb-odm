<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;
use Exception;

use function chr;
use function hexdec;
use function php_uname;
use function preg_match;
use function random_int;
use function sha1;
use function sprintf;
use function str_replace;
use function strlen;
use function substr;

/**
 * Generates UUIDs.
 */
final class UuidGenerator extends AbstractIdGenerator
{
    /**
     * A unique environment value to salt each UUID with.
     */
    protected ?string $salt = null;

    /**
     * Used to set the salt that will be applied to each id
     */
    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
    }

    /**
     * Returns the current salt value
     *
     * @return string|null The current salt
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * Checks that a given string is a valid uuid.
     */
    public function isValid(string $uuid): bool
    {
        return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid)
            === 1;
    }

    /**
     * Generates a new UUID
     *
     * @param DocumentManager $dm       Not used.
     * @param object          $document Not used.
     *
     * @return string UUID
     *
     * @throws Exception
     */
    public function generate(DocumentManager $dm, object $document)
    {
        $uuid = $this->generateV4();

        return $this->generateV5($uuid, $this->salt ?: php_uname('n'));
    }

    /**
     * Generates a v4 UUID
     */
    public function generateV4(): string
    {
        return sprintf(
            '%04x%04x%04x%04x%04x%04x%04x%04x',
            // 32 bits for "time_low"
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            // 16 bits for "time_mid"
            random_int(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            random_int(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            random_int(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
        );
    }

    /**
     * Generates a v5 UUID
     *
     * @throws Exception When the provided namespace is invalid.
     */
    public function generateV5(string $namespace, string $salt): string
    {
        if (! $this->isValid($namespace)) {
            throw new Exception('Provided $namespace is invalid: ' . $namespace);
        }

        // Get hexadecimal components of namespace
        $nhex = str_replace(['-', '{', '}'], '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr((int) hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        // Calculate hash value
        $hash = sha1($nstr . $salt);

        return sprintf(
            '%08s%04s%04x%04x%12s',
            // 32 bits for "time_low"
            substr($hash, 0, 8),
            // 16 bits for "time_mid"
            substr($hash, 8, 4),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 3
            (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
            // 48 bits for "node"
            substr($hash, 20, 12),
        );
    }
}
