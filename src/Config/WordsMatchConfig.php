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

    protected $tempDir;

    protected $serverName = 'words-match';

    protected $processNum = 3;

    protected $run = false;

    protected $backlog = 256;

    protected $defaultWordBank = '';

    protected $maxMem = '512M';

    protected $wordsMatchPath = '';

    protected $separator = ',';

    protected $algorithmType;

    public const DFA='DFA';
    public const AC='AC';

    public function __construct(array $data = null, $autoCreateProperty = false)
    {
        $this->tempDir = getcwd();
        parent::__construct($data, $autoCreateProperty);
    }

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

    public function isRun()
    {
        return $this->run;
    }

    public function getBacklog()
    {
        return $this->backlog;
    }

    public function getDefaultWordBank()
    {
        return $this->defaultWordBank;
    }

    public function getMaxMem()
    {
        return $this->maxMem;
    }

    public function getWordsMatchPath()
    {
        return $this->wordsMatchPath;
    }

    public function getSeparator()
    {
        return $this->separator;
    }

    public function getAlgorithmType()
    {
        return $this->algorithmType;
    }

}