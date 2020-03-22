<?php
namespace EasySwoole\WordsMatch\Ac;

use EasySwoole\WordsMatch\Ac\Cons;

class SparseEdgeList{

    private $head;

    public function get($word) {
        $cons = $this->head;
        while($cons !== null){
            if ($cons->word === $word){
                return $cons->state;
            }
            $cons = $cons->next;
        }
        return null;
    }

    public function put($word, $state){
        $this->head = new Cons($word, $state, $this->head);
    }

    public function keys() {
        $result = array();
        $c = $this->head;
        while($c !== null){
            $result[] = $c->word;
            $c = $c->next;
        }
        return $result;
    }

}
