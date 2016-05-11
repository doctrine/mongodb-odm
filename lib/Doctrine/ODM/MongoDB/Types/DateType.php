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

namespace Doctrine\ODM\MongoDB\Types;

/**
 * The Date type.
 *
 * @since       1.0
 */
class DateType extends Type
{
    /**
     * Converts a value to a DateTime.
     * Supports microseconds
     *
     * @throws InvalidArgumentException if $value is invalid
     * @param  mixed $value \DateTimeInterface|\MongoDate|int|float
     * @return \DateTime
     */
    public static function getDateTime($value)
    {
        $datetime = false;
        $exception = null;

        if ($value instanceof \DateTimeInterface) {
            return $value;
        } elseif ($value instanceof \MongoDate) {
            $microseconds = str_pad($value->usec, 6, '0', STR_PAD_LEFT); // ensure microseconds
            $datetime = static::craftDateTime($value->sec, $microseconds);
        } elseif (is_numeric($value)) {
            $seconds = $value;
            $microseconds = 0;

            if (false !== strpos($value, '.')) {
                list($seconds, $microseconds) = explode('.', $value);
                $microseconds = str_pad($microseconds, 6, '0'); // ensure microseconds
            }

            $datetime = static::craftDateTime($seconds, $microseconds);
        } elseif (is_string($value)) {
            try {
                $datetime = new \DateTime($value);
            } catch (\Exception $e) {
                $exception = $e;
            }
        }

        if ($datetime === false) {
            throw new \InvalidArgumentException(sprintf('Could not convert %s to a date value', is_scalar($value) ? '"'.$value.'"' : gettype($value)), 0, $exception);
        }

        return $datetime;
    }

    private static function craftDateTime($seconds, $microseconds = 0)
    {
        $datetime = new \DateTime();
        $datetime->setTimestamp($seconds);
        if ($microseconds > 0) {
            $datetime = \DateTime::createFromFormat('Y-m-d H:i:s.u', $datetime->format('Y-m-d H:i:s') . '.' . $microseconds);
        }

        return $datetime;
    }

    public function convertToDatabaseValue($value)
    {
        if ($value === null || $value instanceof \MongoDate) {
            return $value;
        }

        $datetime = static::getDateTime($value);

        return new \MongoDate($datetime->format('U'), $datetime->format('u'));
    }

    public function convertToPHPValue($value)
    {
        if ($value === null) {
            return null;
        }

        return static::getDateTime($value);
    }

    public function closureToMongo()
    {
        return 'if ($value === null || $value instanceof \MongoDate) { $return = $value; } else { $datetime = \\'.get_class($this).'::getDateTime($value); $return = new \MongoDate($datetime->format(\'U\'), $datetime->format(\'u\')); }';
    }

    public function closureToPHP()
    {
        return 'if ($value === null) { $return = null; } else { $return = \\'.get_class($this).'::getDateTime($value); }';
    }
}
