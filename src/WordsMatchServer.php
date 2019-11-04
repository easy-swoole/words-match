<?php
/**
 * @CreateTime:   2019/10/21 下午10:29
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词服务端
 */
namespace EasySwoole\WordsMatch;

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

    function __construct()
    {
        $this->tempDir = getcwd();
    }

    /**
     * 设置分隔符
     *
     * @param string $separator
     * @return WordsMatchServer
     * @throws RuntimeError
     * CreateTime: 2019/10/30 上午12:25
     */
    public function setSeparator(string $separator): WordsMatchServer
    {
        $this->modifyCheck();
        $this->separator = $separator;
        return $this;
    }

    /**
     * 设置关键词默认路径
     *
     * @param string $path
     * @return WordsMatchServer
     * @throws RuntimeError
     * CreateTime: 2019/10/30 上午12:25
     */
    public function setWordsMatchPath(string $path): WordsMatchServer
    {
        $this->modifyCheck();
        $this->wordMatchPath = $path;
        return $this;
    }

    /**
     * 设置每个进程所占内存大小
     *
     * @param string $maxMem
     * CreateTime: 2019/10/24 上午1:10
     * @throws RuntimeError
     * @return WordsMatchServer
     */
    public function setMaxMem(string $maxMem='512M'): WordsMatchServer
    {
        $this->modifyCheck();
        $this->maxMem = $maxMem;
        return $this;
    }

    /**
     * 设置临时目录
     *
     * @param string $tempDir
     * @return WordsMatchServer
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:35
     */
    public function setTempDir(string $tempDir): WordsMatchServer
    {
        $this->modifyCheck();
        $this->tempDir = $tempDir;
        return $this;
    }

    /**
     * 设置处理进程数量
     *
     * @param int $num
     * @return WordsMatchServer
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:36
     */
    public function setProcessNum(int $num): WordsMatchServer
    {
        $this->modifyCheck();
        $this->processNum = $num;
        return $this;
    }

    /**
     * 设置UnixSocket的Backlog队列长度
     *
     * @param int|null $backlog
     * @return $this
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:36
     */
    public function setBacklog(?int $backlog = null)
    {
        $this->modifyCheck();
        if ($backlog != null) {
            $this->backlog = $backlog;
        }
        return $this;
    }

    /**
     * 设置服务名称
     *
     * @param string $serverName
     * @return WordsMatchServer
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:36
     */
    public function setServerName(string $serverName): WordsMatchServer
    {
        $this->modifyCheck();
        $this->serverName = $serverName;
        return $this;
    }

    /**
     * 设置内部定时器的回调方法(用于数据落地)
     *
     * @param $onTick
     * @return WordsMatchServer
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:36
     */
    public function setOnTick($onTick): WordsMatchServer
    {
        $this->modifyCheck();
        $this->onTick = $onTick;
        return $this;
    }

    /**
     * 设置默认词库
     *
     * @param string $defaultWordBank
     * @return WordsMatchServer
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午11:30
     */
    public function setDefaultWordBank(string $defaultWordBank): WordsMatchServer
    {
        $this->modifyCheck();
        $this->defaultWordBank = $defaultWordBank;
        return $this;
    }

    /**
     * 启动后就不允许更改设置
     *
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:38
     */
    private function modifyCheck()
    {
        if ($this->run) {
            throw new RuntimeError('you can not modify configure after init process check');
        }
    }

    /**
     * 绑定到当前主服务
     * @param swoole_server $server
     * @throws \Exception
     */
    function attachToServer(swoole_server $server)
    {
        $list = $this->initProcess();
        /** @var $process WordsMatchProcess*/
        foreach ($list as $process) {
            $server->addProcess($process->getProcess());
        }
    }

    /**
     * 初始化缓存进程
     * @return array
     * @throws \Exception
     */
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

    public function append($word, array $otherInfo=[], float $timeout = 1.0)
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

    public function remove($word, float $timeout = 1.0)
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

    public function search($word, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $pack = new Package();
        $pack->setCommand($pack::ACTION_SEARCH);
        $pack->setWord($word);
        return $this->sendAndRecv($this->generateSocket(), $pack, $timeout);
    }

    public function export($fileName, $separator=',', float $timeout=1.0)
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

    public function import($fileName, $separator=',', $isCover=false, float $timeout=1.0)
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
