<?php
/**
 * @CreateTime:   2019/10/22 下午11:01
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词客户端配置
 */
namespace EasySwoole\WordsMatch\Base;

interface WordsMatchClientInter
{

    public function append(string $word, array $otherInfo, float $timeout=1.0);

    public function search(string $word, int $type, float $timeout=1.0);

    public function remove(string $word, float $timeout=1.0);

    public function export(string $fileName, string $separator=',', float $timeout=1.0);

    public function import(string $fileName, string $separator=',', bool $isCover=false, float $timeout=1.0);

}
