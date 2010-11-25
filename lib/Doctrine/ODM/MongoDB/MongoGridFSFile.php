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
 * MongoGridFSFile is a wrapper around the native PHP MongoGridFSFile class.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       1.0
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class MongoGridFSFile
{
    /**
     * Stores \MongoGridFSFile instance or a string path to a file.
     *
     * @var string|\MongoGridFSFile
     */
    private $mongoGridFSFile;

    public function __construct($file)
    {
        $this->mongoGridFSFile = $file;
        if ($file instanceof \MongoGridFSFile) {
            $this->file = $file->file;
        }
    }

    public function getBytes()
    {
        if ($this->mongoGridFSFile instanceof \MongoGridFSFile) {
            return $this->mongoGridFSFile->getBytes();
        }
        return file_get_contents($this->mongoGridFSFile);
    }

    public function getFilename()
    {
        if ($this->mongoGridFSFile instanceof \MongoGridFSFile) {
            return $this->mongoGridFSFile->getFilename();
        }
        $info = pathinfo($this->mongoGridFSFile);
        return $info['name'];
    }

    public function getSize()
    {
        if ($this->mongoGridFSFile instanceof \MongoGridFSFile) {
            return $this->mongoGridFSFile->getSize();
        }
        return filesize($this->mongoGridFSFile);
    }

    public function write($filename)
    {
        if ($this->mongoGridFSFile instanceof \MongoGridFSFile) {
            return $this->mongoGridFSFile->write($filename);
        }
        copy($this->mongoGridFSFile, $filename);
    }
}