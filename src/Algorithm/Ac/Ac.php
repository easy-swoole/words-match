<?php
/**
 * @CreateTime:   2020-03-22 15:26
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  Ac算法
 */
namespace EasySwoole\WordsMatch\Algorithm\Ac;

use EasySwoole\WordsMatch\Base\AlgorithmInter;
use EasySwoole\WordsMatch\Exception\RuntimeError;
use EasySwoole\WordsMatch\Extend\CodeTrans;
use Exception;

class Ac implements AlgorithmInter
{

    private $root;
    private $prepared;
    private $arrKeys;

    public function __construct() {
        $this->root = new State(0);
        $this->root->setFail($this->root);
        $this->prepared = false;
        $this->arrKeys = [];
    }

    public function getRoot() {
        return $this->root;
    }

    public function append(string $word, array $otherInfo){
        if ($this->prepared){
            throw new RuntimeError('cant add keywords after prepare() is called.');
        }
        $word = trim($word);
        $words = CodeTrans::getInstance()->strToChars($word);
        $this->arrKeys = array_unique(array_merge($this->arrKeys, $words));
        $lastState = $this->root->extendAll($words);
        $lastState->addOutput($word);
    }

    private function setFailPointer() {
        $q = [];
        foreach($this->arrKeys as $value){
            if($this->root->get($value) === null){
                $this->root->put($value, $this->root);
            }else{
                $this->root->get($value)->setFail($this->root);
                $q[] = $this->root->get($value);
            }
        }
        while(!empty($q)) {
            $state = array_shift($q);
            if($state === null){
                break;
            }
            $keys = $state->keys();
            foreach ($keys as $iValue) {
                $nowState = $state;

                $nextState = $nowState->get($iValue);
                $q[] = $nextState;

                $nowState = $nowState->getFail();

                while($nowState->get($iValue) === null){
                    $nowState = $nowState->getFail();
                }

                $nextState->setFail($nowState->get($iValue));
                $nextState->setOutputs(array_unique(array_merge($nextState->getOutputs(), $nowState->get($iValue)->getOutputs())));
            }
        }
    }

    public function search(string $words){
        $searcher = new Searcher($this, $this->startSearch($words));
        $res = [];
        while($searcher->hasNext()){
            $result = $searcher->next();
            $res = array_unique(array_merge($res, $result->getOutputs()));
        }
        return $res;
    }

    public function startSearch($words) {
        try{
            if (!$this->prepared){
                throw new RuntimeError("Can't start search until prepare().");
            }
        }catch(Exception $e){
            echo $e->getMessage();
            return false;
        }
        $arrWords = CodeTrans::getInstance()->strToChars($words);
        return $this->continueSearch(new SearchResult($this->root, $arrWords, 0));
    }

    public function continueSearch($lastResult) {
        if($lastResult === null){
            return null;
        }

        $words = $lastResult->words;
        $state = $lastResult->lastMatchedState;
        $start = $lastResult->lastIndex;
        $len = count($words);
        for($i=$start; $i<$len; $i++) {
            $word = $words[$i];
            while ($state->get($word) === null){
                $state = $state->getFail();
                if($state===$this->root){
                    break;
                }
            }

            if($state->get($word) !== null){
                // 获取搜索词对应的State值，如果有输出内容，则输出
                $state = $state->get($word);
                if (count($state->getOutputs())>0){
                    return new SearchResult($state, $words, $i+1);
                }
            }
        }
        return null;
    }

    public function prepare()
    {
        $this->setFailPointer();
        $this->prepared = true;
    }

    public function remove(string $word)
    {
        // TODO: Implement prepare() method.
    }

}