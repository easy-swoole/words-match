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
    protected $word;
    protected $otherInfo=[];
    protected $separator;
    protected $content;
    protected $wordBankName='default';

    public const ACTION_SEARCH = 11;
    public const ACTION_APPEND = 12;
    public const ACTION_REMOVE = 13;

    public function getContent() : string
    {
        return $this->content;
    }

    public function setContent($content): void
    {
        $this->content = $content;
    }

    public function getCommand():int
    {
        return $this->command;
    }

    public function setCommand($command): void
    {
        $this->command = $command;
    }

    public function setWord(string $word)
    {
        $this->word = trim($word);
    }

    public function getWord(): string
    {
        return $this->word;
    }

    public function setOtherInfo($otherInfo): void
    {
        $this->otherInfo = $otherInfo;
    }

    public function getOtherInfo(): array
    {
        return $this->otherInfo;
    }

    public function setWordBankName(string $wordBankName)
    {
        $this->wordBankName = $wordBankName;
    }

    public function getWordBankName()
    {
        return $this->wordBankName;
    }

}
