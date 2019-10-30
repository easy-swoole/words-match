<?php
/**
 * @CreateTime:   2019/10/21 下午11:01
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词服务通讯包
 */
namespace EasySwoole\Keyword\Base;

class Package
{
    protected $command;
    protected $keyword;
    protected $otherInfo=[];
    protected $fileName;
    protected $separator;
    protected $isCover=false;

    const ACTION_SEARCH = 11;
    const ACTION_APPEND = 12;
    const ACTION_REMOVE = 13;
    const ACTION_GET_TREE = 14;
    const ACTION_EXPORT = 15;
    const ACTION_IMPORT = 16;

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

    public function setFileName($fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setSeparator($separator): void
    {
        $this->separator = $separator;
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    public function setCover(bool $cover): void
    {
        $this->isCover = $cover;
    }

    public function getCover(): bool
    {
        return $this->isCover;
    }
}
