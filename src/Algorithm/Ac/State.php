<?php
namespace EasySwoole\WordsMatch\Algorithm\Ac;
use EasySwoole\WordsMatch\Algorithm\Ac\DenseEdgeList;

class State {

    private $depth;
    private $edgeList;
    private $fail;
    private $outputs;

    public function __construct($depth) {
        $this->depth = $depth;
        $this->edgeList = new DenseEdgeList();
        $this->outputs = array();
    }

    public function extend($character) {
        if ($this->edgeList->get($character) === null){
            $nextState = new State($this->depth+1);
            $this->edgeList->put($character, $nextState);
        }
        return $this->edgeList->get($character);
    }

    public function extendAll($contents) {
        $state = $this;
        foreach ($contents as $iValue) {
            if($state->edgeList->get($iValue) === null){
                $state = $state->extend($iValue);
            }else{
                $state = $state->edgeList->get($iValue);
            }
        }
        return $state;
    }

    public function size() {
        $keys = $this->edgeList->keys();
        $result = 1;
        foreach ($keys as $iValue) {
            $result += $this->edgeList->get($iValue)->size();
        }
        return $result;
    }

    public function get($character) {
        return $this->edgeList->get($character);
    }

    public function put($character, $state) {
        $this->edgeList->put($character, $state);
    }

    public function keys() {
        return $this->edgeList->keys();
    }

    public function getFail() {
        return $this->fail;
    }

    public function setFail($state) {
        $this->fail = $state;
    }

    public function addOutput($str) {
        $this->outputs[] = $str;
    }

    public function getOutputs() {
        return $this->outputs;
    }

    public function setOutputs($arr=[]){
        $this->outputs = $arr;
    }
}
