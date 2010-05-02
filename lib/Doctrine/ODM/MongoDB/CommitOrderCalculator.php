<?php

namespace Doctrine\ODM\MongoDB;

class CommitOrderCalculator
{
    const NOT_VISITED = 1;
    const IN_PROGRESS = 2;
    const VISITED = 3;
    
    private $_nodeStates = array();
    private $_classes = array(); // The nodes to sort
    private $_relatedClasses = array();
    private $_sorted = array();
    
    /**
     * Clears the current graph.
     *
     * @return void
     */
    public function clear()
    {
        $this->_classes =
        $this->_relatedClasses = array();
    }
    
    /**
     * Gets a valid commit order for all current nodes.
     * 
     * Uses a depth-first search (DFS) to traverse the graph.
     * The desired topological sorting is the reverse postorder of these searches.
     *
     * @return array The list of ordered classes.
     */
    public function getCommitOrder()
    {
        // Check whether we need to do anything. 0 or 1 node is easy.
        $nodeCount = count($this->_classes);
        if ($nodeCount === 0) {
            return array();
        }

        if ($nodeCount === 1) {
            return array_values($this->_classes);
        }
        
        // Init
        foreach ($this->_classes as $node) {
            $this->_nodeStates[$node->name] = self::NOT_VISITED;
        }
        
        // Go
        foreach ($this->_classes as $node) {
            if ($this->_nodeStates[$node->name] == self::NOT_VISITED) {
                $this->_visitNode($node);
            }
        }

        $sorted = array_reverse($this->_sorted);

        $this->_sorted = $this->_nodeStates = array();

        return $sorted;
    }

    private function _visitNode($node)
    {
        $this->_nodeStates[$node->name] = self::IN_PROGRESS;

        if (isset($this->_relatedClasses[$node->name])) {
            foreach ($this->_relatedClasses[$node->name] as $relatedNode) {
                if ($this->_nodeStates[$relatedNode->name] == self::NOT_VISITED) {
                    $this->_visitNode($relatedNode);
                }
            }
        }

        $this->_nodeStates[$node->name] = self::VISITED;
        $this->_sorted[] = $node;
    }
    
    public function addDependency($fromClass, $toClass)
    {
        $this->_relatedClasses[$fromClass->name][] = $toClass;
    }
    
    public function hasClass($className)
    {
        return isset($this->_classes[$className]);
    }
    
    public function addClass($class)
    {
        $this->_classes[$class->name] = $class;
    }
}