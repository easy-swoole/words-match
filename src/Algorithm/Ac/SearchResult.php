<?php
namespace EasySwoole\WordsMatch\Algorithm\Ac;

class SearchResult {

    public $lastMatchedState;

    public $words;

    public $lastIndex;

    public function __construct($state, $words=array(), $index=0) {
        $this->lastMatchedState = $state;
        $this->words = $words;
        $this->lastIndex = $index;
    }

    public function getOutputs() {
        return $this->lastMatchedState->getOutputs();
    }

    public function getLastIndex() {
        return $this->lastIndex;
    }
}
