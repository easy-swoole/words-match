<?php
namespace EasySwoole\WordsMatch\Algorithm\Ac;

class Cons {

    public $word;
    public $state;
    public $next;

    public function __construct($word, $state, $next){
        $this->word = $word;
        $this->state = $state;
        $this->next = $next;
    }
}
