<?php
namespace EasySwoole\WordsMatch\Ac;

class Searcher{

    private $tree;
    private $currentResult;

    public function __construct($tree, $result) {
        $this->tree = $tree;
        $this->currentResult = $result;
    }

    public function hasNext() {
        return $this->currentResult !== null;
    }

    public function next() {
        if (!$this->hasNext()){
            return NULL;
        }
        $result = $this->currentResult;
        $this->currentResult = $this->tree->continueSearch($this->currentResult);
        return $result;
    }
}
