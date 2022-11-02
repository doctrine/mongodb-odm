<?php

declare(strict_types=1);

namespace Doctrine\ODM\MongoDB\Id;

use Doctrine\ODM\MongoDB\DocumentManager;

use function bccomp;
use function bcdiv;
use function bcmod;
use function is_numeric;
use function str_pad;
use function strlen;

use const STR_PAD_LEFT;

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
 */
final class AlnumGenerator extends IncrementGenerator
{
    protected ?int $pad = null;

    protected bool $awkwardSafeMode = false;

    protected string $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    protected string $awkwardSafeChars = '0123456789BCDFGHJKLMNPQRSTVWXZbcdfghjklmnpqrstvwxz';

    /**
     * Set padding on generated id
     */
    public function setPad(int $pad): void
    {
        $this->pad = $pad;
    }

    /**
     * Enable awkwardSafeMode character set
     */
    public function setAwkwardSafeMode(bool $awkwardSafeMode = false): void
    {
        $this->awkwardSafeMode = $awkwardSafeMode;
    }

    /**
     * Set the character set used for ID generation
     */
    public function setChars(string $chars): void
    {
        $this->chars = $chars;
    }

    public function generate(DocumentManager $dm, object $document)
    {
        $id    = (string) parent::generate($dm, $document);
        $index = $this->awkwardSafeMode ? $this->awkwardSafeChars : $this->chars;
        $base  = (string) strlen($index);

        $out = '';
        do {
            $out = $index[(int) bcmod($id, $base)] . $out;
            $id  = bcdiv($id, $base);
        } while (bccomp($id, '0') === 1);

        if (is_numeric($this->pad)) {
            $out = str_pad($out, $this->pad, '0', STR_PAD_LEFT);
        }

        return $out;
    }
}
