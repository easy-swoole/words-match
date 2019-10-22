<?php
/**
 * @CreateTime:   2019/10/21 下午11:01
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  约定的包格式(关键词服务)
 */
namespace EasySwoole\Keyword;

use EasySwoole\Keyword\Exception\WorningException;

class Package
{
    protected $command;
    protected $keyword;

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
     * @throws WorningException
     * CreateTime: 2019/10/21 下午11:36
     */
    public function setKeyword(string $keyword)
    {
        $keyword = trim($keyword);
        if (empty($keyword)) {
            throw new WorningException('keyword is empty!');
        }
        $this->keyword = $keyword;
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

}
