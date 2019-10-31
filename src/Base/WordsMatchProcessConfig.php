<?php
/**
 * @CreateTime:   2019/10/21 下午10:24
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词进程配置
 */
namespace EasySwoole\WordsMatch\Base;
use EasySwoole\Component\Process\Socket\UnixProcessConfig;

class WordsMatchProcessConfig extends UnixProcessConfig
{
    protected $tempDir;
    protected $backlog;
    protected $workerIndex;
    protected $maxMem = '512M';
    protected $defaultWordBank='';
    protected $exportPath='';
    protected $defaultPath='';
    protected $separator='@es@';
    protected $importPath='';

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

    public function setOnTick($onTick): void
    {
        $this->onTick = $onTick;
    }

    public function setTickInterval($tickInterval): void
    {
        $this->tickInterval = $tickInterval;
    }

    public function setOnStart($onStart): void
    {
        $this->onStart = $onStart;
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

    public function getDefaultWordBank(): string
    {
        return $this->defaultWordBank;
    }

    public function setDefaultWordBank(string $defaultWordBank): void
    {
        $this->defaultWordBank = $defaultWordBank;
    }

    public function setExportPath(string $exportPath): void
    {
        $this->exportPath = $exportPath;
    }

    public function getExportPath(): string
    {
        return $this->exportPath;
    }

    public function setDefaultPath(string $path): void
    {
        $this->defaultPath = $path;
    }

    public function getDefaultPath(): string
    {
        return $this->defaultPath;
    }

    public function setSeparator(string $separator): void
    {
        $this->separator = $separator;
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    public function setImportPath(string $importPath): void
    {
        $this->importPath = $importPath;
    }

    public function getImportPath(): string
    {
        return $this->importPath;
    }

}
