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
 * The BinData type for generic data.
 *
 * @since       1.0
 */
class BinDataType extends Type
{
    /**
     * MongoBinData type
     *
     * The default subtype for BSON binary values is 0, but we cannot use a
     * constant here because it is not available in all versions of the PHP
     * driver.
     *
     * @var integer
     * @see http://php.net/manual/en/mongobindata.construct.php
     * @see http://bsonspec.org/#/specification
     */
    protected $binDataType = 0;

    public function convertToDatabaseValue($value)
    {
        if ($value === null) {
            return null;
        }

        if ( ! $value instanceof \MongoBinData) {
            return new \MongoBinData($value, $this->binDataType);
        }

        if ($value->type !== $this->binDataType) {
            return new \MongoBinData($value->bin, $this->binDataType);
        }

        return $value;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? ($value instanceof \MongoBinData ? $value->bin : $value) : null;
    }

    public function closureToMongo()
    {
        return sprintf('$return = $value !== null ? new \MongoBinData($value, %d) : null;', $this->binDataType);
    }

    public function closureToPHP()
    {
        return '$return = $value !== null ? ($value instanceof \MongoBinData ? $value->bin : $value) : null;';
    }
}
