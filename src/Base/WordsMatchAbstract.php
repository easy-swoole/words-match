<?php
/**
 * @CreateTime:   2020/3/29 下午11:03
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2020) Easyswoole all rights reserved
 * @Description:  客户端、服务端基类
 */
namespace EasySwoole\WordsMatch\Base;
use EasySwoole\WordsMatch\Config\WordsMatchConfig;

abstract class WordsMatchAbstract
{

    public function generateSocket(): string
    {
        $index = rand(1, WordsMatchConfig::getInstance()->getProcessNum());
        return $this->generateSocketByIndex($index);
    }

    public function generateSocketByIndex($index)
    {
        $serverName =  WordsMatchConfig::getInstance()->getServerName() . ".Process.{$index}.sock";
        return  WordsMatchConfig::getInstance()->getTempDir() . "/{$serverName}";
    }
}
