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

namespace Doctrine\ODM\MongoDB\ChangeSet;

final class FieldChange implements \ArrayAccess, ChangedValue
{
    private $oldValue;
    private $newValue;

    public function __construct($oldValue, $newValue)
    {
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
    }

    /**
     * @return mixed
     */
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * @return mixed
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }

    public function offsetExists($offset)
    {
        return in_array($offset, [0, 1], true);
    }

    public function offsetGet($offset)
    {
        switch ($offset) {
            case 0:
                return $this->oldValue;
            case 1:
                return $this->newValue;
            default:
                throw new \OutOfBoundsException();
        }
    }

    public function offsetSet($offset, $value)
    {
        switch ($offset) {
            case 0:
                return $this->oldValue = $value;
            case 1:
                return $this->newValue = $value;
            default:
                throw new \OutOfBoundsException();
        }
    }

    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException('Not allowed.');
    }
}
