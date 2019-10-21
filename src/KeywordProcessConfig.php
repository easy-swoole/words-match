<?php
/**
 * @CreateTime:   2019/10/21 下午10:24
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词进程配置
 */
namespace EasySwoole\Keyword;
use EasySwoole\Component\Process\Socket\UnixProcessConfig;

class KeywordProcessConfig extends UnixProcessConfig
{
    protected $tempDir;
    protected $onTick;
    protected $tickInterval = 5 * 1000;
    protected $onStart;
    protected $onShutdown;
    protected $backlog;
    protected $workerIndex;
    protected $maxMem = '512M';
    protected $queueReserveTime = 60;
    protected $queueMaxReleaseTimes = 10;
    protected $keywordPath='';

    public function getTempDir()
    {
        return $this->tempDir;
    }

    public function setTempDir($tempDir): void
    {
        $this->tempDir = $tempDir;
    }

    public function getProcessName()
    {
        return $this->processName;
    }

    public function setProcessName($processName): void
    {
        $this->processName = $processName;
    }

    public function getOnTick()
    {
        return $this->onTick;
    }

    public function setOnTick($onTick): void
    {
        $this->onTick = $onTick;
    }

    public function getTickInterval()
    {
        return $this->tickInterval;
    }

    public function setTickInterval($tickInterval): void
    {
        $this->tickInterval = $tickInterval;
    }

    public function getOnStart()
    {
        return $this->onStart;
    }

    public function setOnStart($onStart): void
    {
        $this->onStart = $onStart;
    }

    public function getOnShutdown()
    {
        return $this->onShutdown;
    }

    public function setOnShutdown($onShutdown): void
    {
        $this->onShutdown = $onShutdown;
    }

    public function getBacklog(): int
    {
        return $this->backlog;
    }

    public function setBacklog(int $backlog): void
    {
        $this->backlog = $backlog;
    }

    public function getWorkerIndex()
    {
        return $this->workerIndex;
    }

    public function setWorkerIndex($workerIndex): void
    {
        $this->workerIndex = $workerIndex;
    }

    public function getMaxMem(): string
    {
        return $this->maxMem;
    }

    public function setMaxMem(string $maxMem): void
    {
        $this->maxMem = $maxMem;
    }

    public function getQueueReserveTime(): int
    {
        return $this->queueReserveTime;
    }

    public function setQueueReserveTime(int $queueReserveTime): void
    {
        $this->queueReserveTime = $queueReserveTime;
    }

    public function getQueueMaxReleaseTimes(): int
    {
        return $this->queueMaxReleaseTimes;
    }

    public function setQueueMaxReleaseTimes(int $queueMaxReleaseTimes): void
    {
        $this->queueMaxReleaseTimes = $queueMaxReleaseTimes;
    }

    public function getKeywordPath(): string
    {
        return $this->keywordPath;
    }

    public function setKeywordPath(string $keywordPath): void
    {
        $this->keywordPath = $keywordPath;
    }
}
