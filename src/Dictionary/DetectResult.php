<?php


namespace EasySwoole\WordsMatch\Dictionary;


class DetectResult
{
    private $word;
    private $other;
    private $count = 0;
    private $location = [];
    private $type;
}