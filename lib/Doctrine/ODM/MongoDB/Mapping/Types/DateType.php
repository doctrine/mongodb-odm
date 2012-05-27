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

namespace Doctrine\ODM\MongoDB\Mapping\Types;

/**
 * The String type.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class DateType extends Type
{
    public function convertToDatabaseValue($value)
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \MongoDate) {
            return $value;
        }
        $timestamp = false;
        if ($value instanceof \DateTime) {
            $timestamp = $value->format('U');
        } elseif (is_numeric($value)) {
            $timestamp = $value;
        } elseif (is_string($value)) {
            $timestamp = strtotime($value);
        }
        // Could not convert date to timestamp so store ISO 8601 formatted date instead
        if ($timestamp === false) {
            $date = new \DateTime($value);
            return $date->format('c');
        } else {
            $value = $timestamp;
        }
        return new \MongoDate($value);
    }

    public function convertToPHPValue($value)
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \MongoDate) {
            $date = new \DateTime();
            $date->setTimestamp($value->sec);
        } else {
            $date = new \DateTime($value);
        }
        return $date;
    }

    public function closureToMongo()
    {
        return 'if ($value instanceof \DateTime) { $value = $value->getTimestamp(); } else if (is_string($value)) { $value = strtotime($value); } $return = new \MongoDate($value);';
    }

    public function closureToPHP()
    {
        return 'if ($value instanceof \MongoDate) { $date = new \DateTime(); $date->setTimestamp($value->sec); $return = $date; } else { $return = new \DateTime($value); }';
    }
}
