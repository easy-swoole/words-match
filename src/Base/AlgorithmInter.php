<?php
/**
 * @CreateTime:   2020-03-22 15:30
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  检测算法接口
 */
namespace EasySwoole\WordsMatch\Base;

interface AlgorithmInter
{

    public function append(string $word, array $otherInfo);

    public function search(string $word);

    public function getRoot();

    public function prepare();

}