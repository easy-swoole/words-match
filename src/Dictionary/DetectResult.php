<?php


namespace EasySwoole\WordsMatch\Dictionary;


use EasySwoole\Spl\SplBean;

class DetectResult extends SplBean
{
    public $word;
    public $other;
    public $count = 0;
    public $location = [];
    public $type;
}
