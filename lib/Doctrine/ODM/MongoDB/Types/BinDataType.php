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
     * Data type for binary data
     *
     * @var integer
     * @see http://bsonspec.org/#/specification
     */
    protected $binDataType = \MongoDB\BSON\Binary::TYPE_GENERIC;

    public function convertToDatabaseValue($value)
    {
        if ($value === null) {
            return null;
        }

        if ( ! $value instanceof \MongoDB\BSON\Binary) {
            return new \MongoDB\BSON\Binary($value, $this->binDataType);
        }

        if ($value->getType() !== $this->binDataType) {
            return new \MongoDB\BSON\Binary($value->getData(), $this->binDataType);
        }

        return $value;
    }

    public function convertToPHPValue($value)
    {
        return $value !== null ? ($value instanceof \MongoDB\BSON\Binary ? $value->getData() : $value) : null;
    }

    public function closureToMongo()
    {
        return sprintf('$return = $value !== null ? new \MongoDB\BSON\Binary($value, %d) : null;', $this->binDataType);
    }

    public function closureToPHP()
    {
        return '$return = $value !== null ? ($value instanceof \MongoDB\BSON\Binary ? $value->getData() : $value) : null;';
    }
}
