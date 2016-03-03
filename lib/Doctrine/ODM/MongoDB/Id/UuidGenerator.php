<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;

/**
 * Generates UUIDs.
 *
 * @since       1.0
 */
class UuidGenerator extends AbstractIdGenerator
{
    /**
     * A unique environment value to salt each UUID with.
     *
     * @var string
     */
    protected $salt = null;

    /**
     * Used to set the salt that will be applied to each id
     *
     * @param string $salt The sale to use
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * Returns the current salt value
     *
     * @return string $salt The current salt
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Checks that a given string is a valid uuid.
     *
     * @param string $uuid The string to check.
     * @return boolean
     */
    public function isValid($uuid)
    {
        return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid)
            === 1;
    }

    /**
     * Generates a new UUID
     *
     * @param DocumentManager $dm Not used.
     * @param object $document Not used.
     * @return string UUID
     */
    public function generate(DocumentManager $dm, $document)
    {
        $uuid = $this->generateV4();
        return $this->generateV5($uuid, $this->salt ?: php_uname('n'));
    }

    /**
     * Generates a v4 UUID
     *
     * @return string
     */
    public function generateV4()
    {
        return sprintf(
            '%04x%04x%04x%04x%04x%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Generates a v5 UUID
     *
     * @param string $namespace The UUID to seed with
     * @param string $salt The string to salt this new UUID with
     * @throws \Exception when the provided namespace is invalid
     * @return string
     */
    public function generateV5($namespace, $salt)
    {
        if ( ! $this->isValid($namespace)) {
            throw new \Exception('Provided $namespace is invalid: ' . $namespace);
        }

        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-', '{', '}'), '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
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
            substr($hash, 20, 12)
        );
    }
}
