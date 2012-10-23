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

namespace Doctrine\ODM\MongoDB\Mapping;

/**
 * This interface should be implemented by (static) initializers which were
 * registered in the <tt>ClassMetdata</tt>, either via registerInitializers()
 * or via registerStaticInitializers.
 *
 * <i>Note: Document instances created with the <b>new</b> keyword will not be
 * initialized. For this reason it will be necessary to use</i>
 *      <code>$dm->getClassMetadata('MyDocument')->newInstance();</code>
 * <i>to create a new instance of a document.</i>
 *
 * @author      Christian Gahlert <extr3m0@email.de>
 */
interface DocumentInitializerInterface
{
    /**
     * New document prototype-instances created by ClassMetadata::newInstance()
     * will be passed to this method so one could e.g. pass dependencies
     * on to the document before using it.
     * 
     * @param The new prototype instance.
     */
    abstract public function initialize($instance);
}