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
 * MongoGridFSFile is a wrapper around the native PHP MongoGridFSFile class and allows you
 * to use an instance of this class to persist new files as well as represent existing already
 * persisted files using the MongoGridFS.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class MongoGridFSFile
{
    /**
     * Stores \MongoGridFSFile instance.
     *
     * @var \MongoGridFSFile
     */
    private $mongoGridFSFile;

    /**
     * Path to a file that is/was pending persistence.
     *
     * @var string
     */
    private $filename;

    /**
     * Bytes that are/were pending persistence.
     *
     * @var string
     */
    private $bytes;

    /**
     * Whether or not the file is dirty and needs to be persisted.
     *
     * @var string
     */
    private $isDirty = false;

    /**
     * Constructs a new dirty file that needs persisting or wraps an existing PHP \MongoGridFSFile
     * instance and does not need persistence unless changed and becomes dirty.
     *
     * @param string|\MongoGridFSFile $file
     */
    public function __construct($file = null)
    {
        if ($file instanceof \MongoGridFSFile) {
            $this->mongoGridFSFile = $file;
            $this->isDirty = false;
        } elseif (is_string($file)) {
            $this->filename = $file;
            $this->isDirty = true;
        }
    }

    /**
     * Sets the persistent MongoGridFSFile instance
     *
     * @param \MongoGridFSFile $mongoGridFSFile
     */
    public function setMongoGridFSFile(\MongoGridFSFile $mongoGridFSFile)
    {
        $this->mongoGridFSFile = $mongoGridFSFile;
        $this->isDirty = false;
    }

    /**
     * Gets the persistent MongoGridFSFile instance
     *
     * @return \MongoGridFSFile
     */
    public function getMongoGridFSFile()
    {
        return $this->mongoGridFSFile;
    }

    /**
     * Set a new filename to be persisted and marks the file as dirty.
     *
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
        $this->isDirty = true;
    }

    /**
     * Gets the filename for this file.
     *
     * @return string $filename
     */
    public function getFilename()
    {
        if ($this->isDirty && $this->filename) {
            return $this->filename;
        } elseif ($this->mongoGridFSFile instanceof \MongoGridFSFile && $filename = $this->mongoGridFSFile->getFilename()) {
            return $filename;
        }
        return $this->filename;
    }

    /**
     * Sets new bytes to be persisted and marks the file as dirty.
     *
     * @param string $bytes
     */
    public function setBytes($bytes)
    {
        $this->bytes = $bytes;
        $this->isDirty = true;
    }

    /**
     * Gets the bytes for this file.
     *
     * @return string $bytes
     */
    public function getBytes()
    {
        if ($this->isDirty && $this->bytes) {
            return $this->bytes;
        }
        if ($this->filename) {
            return file_get_contents($this->filename);
        }
        if ($this->mongoGridFSFile instanceof \MongoGridFSFile) {
            return $this->mongoGridFSFile->getBytes();
        }
        return null;
    }

    /**
     * Gets the size of this file.
     *
     * @return integer $size
     */
    public function getSize()
    {
        if ($this->isDirty && $this->bytes) {
            return strlen($this->bytes);
        }
        if ($this->isDirty && $this->filename) {
            return filesize($this->filename);
        }
        if ($this->mongoGridFSFile instanceof \MongoGridFSFile) {
            return $this->mongoGridFSFile->getSize();
        }
        return 0;
    }

    /**
     * Writes this file to the given filename path.
     *
     * @param string $filename
     * @return boolean TRUE if successful, and FALSE otherwise.
     */
    public function write($filename)
    {
        if ($this->isDirty && $this->bytes) {
            return file_put_contents($filename, $this->bytes);
        }
        if ($this->isDirty && $this->filename) {
            return copy($this->filename, $filename);
        }
        if ($this->mongoGridFSFile instanceof \MongoGridFSFile) {
            return $this->mongoGridFSFile->write($filename);
        }
        throw new \BadMethodCallException('Nothing to write(). File is not persisted yet and is not dirty.');
    }

    /**
     * Check if the file is dirty or set isDirty by passing a boolean argument.
     *
     * @param boolean $bool
     * @param boolean $isDirty
     */
    public function isDirty($bool = null)
    {
        if ($bool !== null) {
            $this->isDirty = $bool;
        }
        return $this->isDirty;
    }

    /**
     * Checks whether the file has some unpersisted bytes.
     *
     * @return boolean
     */
    public function hasUnpersistedBytes()
    {
        return $this->isDirty && $this->bytes ? true : false;
    }

    /**
     * Checks whether the file has a unpersisted file.
     *
     * @return boolean
     */
    public function hasUnpersistedFile()
    {
        return $this->isDirty && $this->filename ? true : false;
    }
}