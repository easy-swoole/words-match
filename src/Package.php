<?php
/**
 * @CreateTime:   2019/10/21 下午11:01
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词服务通讯包
 */
namespace EasySwoole\Keyword;

class Package
{
    protected $command;
    protected $keyword;
    protected $otherInfo=[];

    const ACTION_SEARCH = 11;
    const ACTION_APPEND = 12;
    const ACTION_REMOVE = 13;
    const ACTION_GET_TREE = 14;

    public function getCommand():int
    {
        return $this->command;
    }

    public function setCommand($command): void
    {
        $this->command = $command;
    }

    /**
     * 设置关键词
     *
     * @param string $keyword
     * CreateTime: 2019/10/21 下午11:36
     */
    public function setKeyword(string $keyword)
    {
        $this->keyword = trim($keyword);
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function setOtherInfo($otherInfo): void
    {
        $this->otherInfo = $otherInfo;
    }

    public function getOtherInfo(): array
    {
        return $this->otherInfo;
    }
}
