<?php
/**
 * @CreateTime:   2019/10/21 下午11:01
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词服务通讯包
 */
namespace EasySwoole\WordsMatch\Extend\Protocol;

class Package
{
    protected $command;
    protected $content;

    public const ACTION_SEARCH = 11;

    public function getCommand():int
    {
        return $this->command;
    }

    public function setCommand($command): void
    {
        $this->command = $command;
    }

    public function setContent(string $word)
    {
        $this->word = trim($word);
    }

    public function getContent(): string
    {
        return $this->content;
    }

}
