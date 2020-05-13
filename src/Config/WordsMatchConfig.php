<?php
/**
 * @CreateTime:   2020-03-22 17:29
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2020) Easyswoole all rights reserved
 * @Description:  组件配置
 */
namespace EasySwoole\WordsMatch\Config;

use EasySwoole\Component\Singleton;
use EasySwoole\Spl\SplBean;

class WordsMatchConfig extends SplBean
{

    use Singleton;

    protected $tempDir=EASYSWOOLE_ROOT.'/Temp/';

    protected $serverName = 'words-match';

    protected $processNum = 3;

    protected $backlog = 256;

    protected $wordBank = '';

    protected $maxMem = 512;

    protected $separator = ',';

    public function getTempDir()
    {
        return $this->tempDir;
    }

    public function getServerName()
    {
        return $this->serverName;
    }

    public function getProcessNum()
    {
        return $this->processNum;
    }

    public function getBacklog()
    {
        return $this->backlog;
    }

    public function getWordBank()
    {
        return $this->wordBank;
    }

    public function getMaxMem()
    {
        return $this->maxMem;
    }

    public function getSeparator()
    {
        return $this->separator;
    }

}
