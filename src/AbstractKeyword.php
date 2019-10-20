<?php
/**
 * @CreateTime:   2019-10-17 20:55
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词抽象类
 */
namespace EasySwoole\Keyword;
use EasySwoole\Keyword\TreeManager;
use EasySwoole\Keyword\KeywordConfig;
use EasySwoole\Keyword\KeywordInter;

abstract class AbstractKeyword implements KeywordInter
{

    protected $config;

    protected $tree;

    public function __construct(KeywordConfig $config)
    {
        $this->config = $config;
        $this->tree = new TreeManager();

        switch ($config->getSourceType())
        {
            case KeywordConfig::FILE:
                $this->sourceFile();
                break;
            default:
                break;
        }
    }

    private function sourceFile()
    {
        $keywordFile = $this->config->getLibraryPath();
        $file = fopen($keywordFile, 'ab+');
        while (!feof($file)) {
            $line = trim(fgets($file));
            $lineArr = explode("\t", $line);
            $keyword = array_shift($lineArr);
            $this->append($keyword, $lineArr);
        }
    }

}