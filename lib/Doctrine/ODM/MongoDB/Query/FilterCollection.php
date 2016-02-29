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

namespace Doctrine\ODM\MongoDB\Query;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Mapping\ClassMetadata;

/**
 * Collection class for all the query filters.
 */
class FilterCollection
{
    /**
     * The used Configuration.
     *
     * @var \Doctrine\ODM\MongoDB\Configuration
     */
    private $config;

    /**
     * The DocumentManager that "owns" this FilterCollection instance.
     *
     * @var \Doctrine\ODM\MongoDB\DocumentManager
     */
    private $dm;

    /**
     * Instances of enabled filters.
     *
     * @var array
     */
    private $enabledFilters = array();

    /**
     * Constructor.
     *
     * @param DocumentManager $dm
     * @param CriteriaMerger  $cm
     */
    public function __construct(DocumentManager $dm, CriteriaMerger $cm = null)
    {
        $this->dm = $dm;
        $this->cm = $cm ?: new CriteriaMerger();

        $this->config = $dm->getConfiguration();
    }

    /**
     * Get all the enabled filters.
     *
     * @return array The enabled filters.
     */
    public function getEnabledFilters()
    {
        return $this->enabledFilters;
    }

    /**
     * Enables a filter from the collection.
     *
     * @param string $name Name of the filter.
     *
     * @throws \InvalidArgumentException If the filter does not exist.
     *
     * @return \Doctrine\ODM\MongoDB\Query\Filter\BsonFilter The enabled filter.
     */
    public function enable($name)
    {
        if ( ! $this->has($name)) {
            throw new \InvalidArgumentException("Filter '" . $name . "' does not exist.");
        }

        if ( ! $this->isEnabled($name)) {
            $filterClass = $this->config->getFilterClassName($name);
            $filterParameters = $this->config->getFilterParameters($name);
            $filter = new $filterClass($this->dm);

            foreach ($filterParameters as $param => $value) {
                $filter->setParameter($param, $value);
            }

            $this->enabledFilters[$name] = $filter;
        }

        return $this->enabledFilters[$name];
    }

    /**
     * Disables a filter.
     *
     * @param string $name Name of the filter.
     *
     * @return \Doctrine\ODM\MongoDB\Query\Filter\BsonFilter The disabled filter.
     *
     * @throws \InvalidArgumentException If the filter does not exist.
     */
    public function disable($name)
    {
        // Get the filter to return it
        $filter = $this->getFilter($name);

        unset($this->enabledFilters[$name]);

        return $filter;
    }

    /**
     * Get an enabled filter from the collection.
     *
     * @param string $name Name of the filter.
     *
     * @return \Doctrine\ODM\MongoDB\Query\Filter\BsonFilter The filter.
     *
     * @throws \InvalidArgumentException If the filter is not enabled.
     */
    public function getFilter($name)
    {
        if ( ! $this->isEnabled($name)) {
            throw new \InvalidArgumentException("Filter '" . $name . "' is not enabled.");
        }
        return $this->enabledFilters[$name];
    }

    /**
     * Checks whether filter with given name is defined.
     *
     * @param string $name Name of the filter.
     * @return bool true if the filter exists, false if not.
     */
    public function has($name)
    {
        return null !== $this->config->getFilterClassName($name);
    }

    /**
     * Checks whether filter with given name is enabled.
     *
     * @param string $name Name of the filter
     * @return bool
     */
    public function isEnabled($name)
    {
        return isset($this->enabledFilters[$name]);
    }

    /**
     * Gets enabled filter criteria.
     *
     * @param ClassMetadata $class
     * @return array
     */
    public function getFilterCriteria(ClassMetadata $class)
    {
        if (empty($this->enabledFilters)) {
            return array();
        }

        return call_user_func_array(
            array($this->cm, 'merge'),
            array_map(
                function($filter) use ($class) { return $filter->addFilterCriteria($class); },
                $this->enabledFilters
            )
        );
    }
}
