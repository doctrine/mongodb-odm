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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ODM\MongoDB;

/**
 * Wrapper for the PHP MongoDB class.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class MongoDB
{
    /** The PHP MongoDB instance being wrapped */
    private $mongoDB;

    /**
     * Create a new MongoDB instance which wraps a PHP MongoDB instance.
     *
     * @param MongoDB $mongoDB  The MongoDB instance to wrap.
     */
    public function __construct(\MongoDB $mongoDB)
    {
        $this->mongoDB = $mongoDB;
    }

    /**
     * Gets the name of this database
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->__toString();
    }

    /**
     * Get the MongoDB instance being wrapped.
     *
     * @return MongoDB $mongoDB
     */
    public function getMongoDB()
    {
        return $this->mongoDB;
    }

    /** @proxy */
    public function authenticate($username, $password)
    {
        return $this->mongoDB->authenticate($username, $password);
    }

    /** @proxy */
    public function command(array $data)
    {
        return $this->mongoDB->command($data);
    }

    /** @proxy */
    public function createCollection($name, $capped = false, $size = 0, $max = 0)
    {
        return $this->mongoDB->createCollection($name, $capped, $size, $max);
    }

    /** @proxy */
    public function createDBRef($collection, $a)
    {
        return $this->mongoDB->createDBRef($collection, $a);
    }

    /** @proxy */
    public function drop()
    {
        return $this->mongoDB->drop();
    }

    /** @proxy */
    public function dropCollection($coll)
    {
        return $this->mongoDB->dropCollection($coll);
    }

    /** @proxy */
    public function execute($code, array $args = array())
    {
        return $this->mongoDB->execute($code, $args);
    }

    /** @proxy */
    public function forceError()
    {
        return $this->mongoDB->forceError();
    }

    /** @proxy */
    public function __get($name)
    {
        return $this->mongoDB->__get($name);
    }

    /** @proxy */
    public function getDBRef(array $ref)
    {
        return $this->mongoDB->getDBRef($ref);
    }

    /** @proxy */
    public function getGridFS($prefix = 'fs')
    {
        return $this->mongoDB->getGridFS($prefix);
    }

    /** @proxy */
    public function getProfilingLevel()
    {
        return $this->mongoDB->getProfilingLevel();
    }

    /** @proxy */
    public function lastError()
    {
        return $this->mongoDB->lastError();
    }

    /** @proxy */
    public function listCollections()
    {
        return $this->mongoDB->listCollections();
    }

    /** @proxy */
    public function prevError()
    {
        return $this->mongoDB->prevError();
    }

    /** @proxy */
    public function repair($preserveClonedFiles = false, $backupOriginalFiles = false)
    {
        return $this->mongoDB->repair($preserveClonedFiles, $backupOriginalFiles);
    }

    /** @proxy */
    public function resetError()
    {
        return $this->mongoDB->resetError();
    }

    /** @proxy */
    public function selectCollection($name)
    {
        return $this->mongoDB->selectCollection($name);
    }

    /** @proxy */
    public function setProfilingLevel($level)
    {
        return $this->mongoDB->setProfilingLevel($level);
    }

    /** @proxy */
    public function __toString()
    {
        return $this->mongoDB->__toString();
    }
}