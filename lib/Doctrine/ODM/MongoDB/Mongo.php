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
 * Wrapper for the PHP Mongo class.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Mongo
{
    /** The PHP Mongo instance. */
    private $mongo;

    /** The server string */
    private $server;

    /** The array of server options to use when connecting */
    private $options = array();

    /**
     * Create a new Mongo wrapper instance.
     *
     * @param mixed $server A string server name, an existing Mongo instance or can be omitted.
     * @param array $options 
     */
    public function __construct($server = null, array $options = array())
    {
        if ($server instanceof \Mongo) {
            $this->mongo = $server;
        } elseif ($server !== null) {
            $this->server = $server;
            $this->options = $options;
        }
    }

    /**
     * Set the PHP Mongo instance to wrap.
     *
     * @param Mongo $mongo The PHP Mongo instance
     */
    public function setMongo(\Mongo $mongo)
    {
        $this->mongo = $mongo;
    }

    /**
     * Returns the PHP Mongo instance being wrapped.
     *
     * @return Mongo
     */
    public function getMongo()
    {
        if ($this->mongo === null) {
            if ($this->server) {
                $this->mongo = new \Mongo($this->server, $this->options);
            } else {
                $this->mongo = new \Mongo();
            }
        }
        return $this->mongo;
    }

    /** @proxy */
    public function close()
    {
        return $this->getMongo()->close();
    }

    /** @proxy */
    public function connect()
    {
        return $this->getMongo()->connect();
    }

    /** @proxy */
    public function connectUntil()
    {
        return $this->getMongo()->connectUntil();
    }

    /** @proxy */
    public function dropDB($db)
    {
        return $this->getMongo()->dropDB($db);
    }

    /** @proxy */
    public function __get($key)
    {
        return $this->getMongo()->$key;
    }

    /** @proxy */
    public function listDBs()
    {
        return $this->getMongo()->listDBs();
    }

    /** @proxy */
    public function selectCollection($db, $collection)
    {
        return $this->getMongo()->selectCollection($db, $collection);
    }

    /** @proxy */
    public function selectDB($name)
    {
        return $this->getMongo()->selectDB($name);
    }

    /** @proxy */
    public function __toString()
    {
        return $this->getMongo()->__toString();
    }
}