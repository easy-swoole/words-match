<?php
/**
 * @CreateTime:   2019/10/21 下午10:24
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词进程配置
 */
namespace EasySwoole\WordsMatch\Config;
use EasySwoole\Component\Process\Socket\UnixProcessConfig;

class WordsMatchProcessConfig extends UnixProcessConfig
{
    protected $tempDir;
    protected $backlog;
    protected $workerIndex;
    protected $maxMem = '512M';
    protected $processName;

    public function getTempDir()
    {
        return $this->tempDir;
    }

    public function setTempDir($tempDir)
    {
        $this->tempDir = $tempDir;
    }

    public function getProcessName()
    {
        return $this->processName;
    }

    public function setProcessName($processName) : void
    {
        $this->processName = $processName;
    }

    public function getBacklog(): int
    {
        return $this->backlog;
    }

    public function setBacklog(int $backlog)
    {
        $this->backlog = $backlog;
    }

    public function getWorkerIndex()
    {
        return $this->workerIndex;
    }

    public function setWorkerIndex($workerIndex)
    {
        $this->workerIndex = $workerIndex;
    }

    public function getMaxMem(): string
    {
        return $this->maxMem;
    }

    public function setMaxMem(string $maxMem)
    {
        $this->maxMem = $maxMem;
    }

}
