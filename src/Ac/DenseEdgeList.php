<?php
namespace EasySwoole\WordsMatch\Ac;

class DenseEdgeList{
    private $array;

    public function __construct() {
        $this->array = array();
    }

    public function fromSparse($list) {
        $keys = $list->keys();
        $newInstance = new DenseEdgeList();
        for($i=0, $iMax = count($keys); $i< $iMax; $i++) {
            $newInstance->put($keys[$i], $list->get($keys[$i]));
        }
        return $newInstance;
    }

    public function get($word) {
        if(array_key_exists($word, $this->array)){
            return $this->array[$word];
        }

        return null;
    }

    public function put($word, $state) {
        $this->array[$word] = $state;
    }

    public function keys() {
        return array_keys($this->array);
    }
}
