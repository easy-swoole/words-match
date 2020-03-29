<?php

use EasySwoole\WordsMatch\Config\WordsMatchConfig;

/**
 * @CreateTime:   2020/3/29 下午11:03
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2020) Easyswoole all rights reserved
 * @Description:  客户端、服务端基类
 */
namespace EasySwoole\WordsMatch\Base;

abstract class WordsMatchAbstract
{

    /** @var $config WordsMatchConfig */
    protected $config;

    protected function generateSocket(): string
    {
        $index = rand(1, $this->config->getProcessNum());
        return $this->generateSocketByIndex($index);
    }

    protected function generateSocketByIndex($index)
    {
        return $this->config->getTempDir() . "/{$this->config->getServerName()}.Process.{$index}.sock";
    }
}
