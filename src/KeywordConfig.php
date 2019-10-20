<?php
/**
 * @CreateTime:   2019-10-17 23:06
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词配置
 */
namespace EasySwoole\Keyword;

use Exception;

class KeywordConfig
{
    public const FILE = 1;

    private $libraryPath='';

    private $sourceType;

    public function setSourceType(int $type): KeywordConfig
    {
        $this->sourceType = $type;
        return $this;
    }

    public function getSourceType(): int
    {
        return $this->sourceType;
    }

    public function setLibraryPath(string $libraryPath)
    {
        if (!is_file($libraryPath)) {
            throw new Exception('No file path:'.$libraryPath);
        }
        $this->libraryPath = $libraryPath;
    }

    public function getLibraryPath(): string
    {
        return $this->libraryPath;
    }

}