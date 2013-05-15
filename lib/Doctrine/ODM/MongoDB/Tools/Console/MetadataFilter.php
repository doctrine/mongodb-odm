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

namespace Doctrine\ODM\MongoDB\Tools\Console;

/**
 * Used by CLI Tools to restrict entity-based commands to given patterns.
 *
 * @since       1.0
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Jonathan Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class MetadataFilter extends \FilterIterator implements \Countable
{
    /**
     * Filter Metadatas by one or more filter options.
     *
     * @param array $metadatas
     * @param array|string $filter
     * @return array
     */
    static public function filter(array $metadatas, $filter)
    {
        $metadatas = new MetadataFilter(new \ArrayIterator($metadatas), $filter);
        return iterator_to_array($metadatas);
    }

    /**
     * @var array
     */
    private $_filter = array();

    /**
     * @param \ArrayIterator $metadata
     * @param array|string $filter
     */
    public function __construct(\ArrayIterator $metadata, $filter)
    {
        $this->_filter = (array) $filter;
        parent::__construct($metadata);
    }

    /**
     * @return bool
     */
    public function accept()
    {
        if (count($this->_filter) == 0) {
            return true;
        }

        $it = $this->getInnerIterator();
        $metadata = $it->current();

        foreach ($this->_filter AS $filter) {
            if (strpos($metadata->name, $filter) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->getInnerIterator());
    }
}
