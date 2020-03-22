<?php
/**
 * @CreateTime:   2019/10/21 下午10:29
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词服务端
 */
namespace EasySwoole\WordsMatch;

use EasySwoole\WordsMatch\Base\SpecialSymbolFilter;
use swoole_server;
use EasySwoole\Component\Singleton;
use EasySwoole\WordsMatch\Base\Package;
use EasySwoole\WordsMatch\Base\Protocol;
use EasySwoole\WordsMatch\Base\UnixClient;
use EasySwoole\WordsMatch\Base\WordsMatchProcess;
use EasySwoole\WordsMatch\Base\WordsMatchClientInter;
use EasySwoole\WordsMatch\Base\WordsMatchProcessConfig;
use EasySwoole\WordsMatch\Exception\RuntimeError;

class WordsMatchServer implements WordsMatchClientInter
{
    use Singleton;

    private $tempDir;
    private $serverName = 'words-match';
    private $processNum = 3;
    private $run = false;
    private $backlog = 256;
    private $defaultWordBank = '';
    private $maxMem = '512M';
    private $wordMatchPath = '';
    private $separator=',';
    private $algorithmType; // 算法类型

    public const DFA='DFA';
    public const AC='AC';

    function __construct()
    {
        $this->tempDir = getcwd();
    }

    public function setAlgorithmType($type=self::DFA)
    {
        $this->algorithmType = $type;
        return $this;
    }

    public function setSeparator(string $separator): WordsMatchServer
    {
        $this->modifyCheck();
        $this->separator = $separator;
        return $this;
    }

    public function setWordsMatchPath(string $path): WordsMatchServer
    {
        $this->modifyCheck();
        $this->wordMatchPath = $path;
        return $this;
    }

    public function setMaxMem(string $maxMem='512M'): WordsMatchServer
    {
        $this->modifyCheck();
        $this->maxMem = $maxMem;
        return $this;
    }

    public function setTempDir(string $tempDir): WordsMatchServer
    {
        $this->modifyCheck();
        $this->tempDir = $tempDir;
        return $this;
    }

    public function setProcessNum(int $num): WordsMatchServer
    {
        $this->modifyCheck();
        $this->processNum = $num;
        return $this;
    }

    public function setBacklog(?int $backlog = null)
    {
        $this->modifyCheck();
        if ($backlog != null) {
            $this->backlog = $backlog;
        }
        return $this;
    }

    public function setServerName(string $serverName): WordsMatchServer
    {
        $this->modifyCheck();
        $this->serverName = $serverName;
        return $this;
    }

    public function setOnTick($onTick): WordsMatchServer
    {
        $this->modifyCheck();
        $this->onTick = $onTick;
        return $this;
    }

    public function setDefaultWordBank(string $defaultWordBank): WordsMatchServer
    {
        $this->modifyCheck();
        $this->defaultWordBank = $defaultWordBank;
        return $this;
    }

    private function modifyCheck()
    {
        if ($this->run) {
            throw new RuntimeError('you can not modify configure after init process check');
        }
    }

    function attachToServer(swoole_server $server)
    {
        $list = $this->initProcess();
        /** @var $process WordsMatchProcess*/
        foreach ($list as $process) {
            $server->addProcess($process->getProcess());
        }
    }

    private function initProcess(): array
    {
        $this->run = true;
        $array = [];
        for ($i = 1; $i <= $this->processNum; $i++) {
            $config = new WordsMatchProcessConfig();
            $config->setProcessName("{$this->serverName}.Process.{$i}");
            $config->setSocketFile($this->generateSocketByIndex($i));
            $config->setTempDir($this->tempDir);
            $config->setBacklog($this->backlog);
            $config->setAsyncCallback(false);
            $config->setWorkerIndex($i);
            $config->setWordsMatchPath($this->wordMatchPath);
            $config->setDefaultWordBank($this->defaultWordBank);
            $config->setMaxMem($this->maxMem);
            $config->setSeparator($this->separator);
            $config->setAlgorithmType($this->algorithmType);
            $array[$i] = new WordsMatchProcess($config);
        }
        return $array;
    }

    private function generateSocketByIndex($index)
    {
        return $this->tempDir . "/{$this->serverName}.WordsMatchProcess.{$index}.sock";
    }

    private function sendAndRecv($socketFile, Package $package, $timeout)
    {
        $client = new UnixClient($socketFile);
        $client->send(Protocol::pack(serialize($package)));
        $ret = $client->recv($timeout);
        if (!empty($ret)) {
            $ret = unserialize(Protocol::unpack((string)$ret));
            if ($ret instanceof Package) {
                return $ret->getValue();
            }else {
                return $ret;
            }
        }
        return null;
    }

    private function generateSocket(): string
    {
        $index = rand(1, $this->processNum);
        return $this->generateSocketByIndex($index);
    }

    public function append(string $word, array $otherInfo=[], float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $pack = new Package();
        $pack->setCommand($pack::ACTION_APPEND);
        $pack->setWord($word);
        $pack->setOtherInfo($otherInfo);
        for ($i=1;$i<=$this->processNum;$i++){
            $this->sendAndRecv($this->generateSocketByIndex($i), $pack, $timeout);
        }
    }

    public function remove(string $word, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $pack = new Package();
        $pack->setCommand($pack::ACTION_REMOVE);
        $pack->setWord($word);
        for ($i=1;$i<=$this->processNum;$i++){
            $this->sendAndRecv($this->generateSocketByIndex($i), $pack, $timeout);
        }
    }

    public function search(string $word, int $type=0, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $pack = new Package();
        $pack->setCommand($pack::ACTION_SEARCH);
        $pack->setFilterType($type);
        $pack->setWord($word);
        return $this->sendAndRecv($this->generateSocket(), $pack, $timeout);
    }

    public function export(string $fileName, string $separator=',', float $timeout=1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $pack = new Package();
        $pack->setCommand($pack::ACTION_EXPORT);
        $pack->setFileName($fileName);
        $pack->setSeparator($separator);
        return $this->sendAndRecv($this->generateSocket(), $pack, $timeout);
    }

    public function import(string $fileName, string $separator=',', bool $isCover=false, float $timeout=1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $pack = new Package();
        $pack->setCommand($pack::ACTION_IMPORT);
        $pack->setFileName($fileName);
        $pack->setSeparator($separator);
        $pack->setCover($isCover);
        for ($i=1;$i<=$this->processNum;$i++){
            $this->sendAndRecv($this->generateSocketByIndex($i), $pack, $timeout);
        }
    }

}
