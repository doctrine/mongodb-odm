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
 * AlnumGenerator is responsible for generating cased alpha-numeric unique identifiers.
 * It extends IncrementGenerator in order to ensure uniqueness even with short strings.
 *
 * "Awkward safe mode" avoids combinations that results in 'dirty' words by removing
 * the vowels from chars index
 *
 * A minimum identifier length can be enforced by setting a numeric value to the "pad" option
 * (with only 6 chars you will have more than 56 billion unique id's, 15 billion in 'awkward safe mode')
 *
 * The character set used for ID generation can be explicitly set with the "chars" option (e.g. base36, etc.)
 *
 * @since       1.0
 */
class AlnumGenerator extends IncrementGenerator
{

    protected $pad = null;

    protected $awkwardSafeMode = false;

    protected $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    protected $awkwardSafeChars = '0123456789BCDFGHJKLMNPQRSTVWXZbcdfghjklmnpqrstvwxz';

    /**
     * Set padding on generated id
     *
     * @param int $pad
     */
    public function setPad($pad)
    {
        $this->pad = intval($pad);
    }

    /**
     * Enable awkwardSafeMode character set
     *
     * @param bool $awkwardSafeMode
     */
    public function setAwkwardSafeMode($awkwardSafeMode = false)
    {
        $this->awkwardSafeMode = $awkwardSafeMode;
    }

    /**
     * Set the character set used for ID generation
     *
     * @param string $chars ID character set
     */
    public function setChars($chars)
    {
        $this->chars = $chars;
    }

    /** @inheritDoc */
    public function generate(DocumentManager $dm, $document)
    {
        $id = parent::generate($dm, $document);
        $index = $this->awkwardSafeMode ? $this->awkwardSafeChars : $this->chars;
        $base  = strlen($index);

        $out = '';
        do {
            $out = $index[bcmod($id, $base)] . $out;
            $id = bcdiv($id, $base);
        } while (bccomp($id, 0) == 1);

        if (is_numeric($this->pad)) {
            $out = str_pad($out, $this->pad, '0', STR_PAD_LEFT);
        }

        return $out;
    }
}
