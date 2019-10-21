<?php
/**
 * @CreateTime:   2019/10/21 下午10:29
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词服务基础类
 */
namespace EasySwoole\Keyword;

use EasySwoole\Component\Singleton;
use EasySwoole\Keyword\Exception\RuntimeError;
use swoole_server;
class Keyword
{
    use Singleton;

    private $tempDir;
    private $serverName = 'EasySwoole Keyword';
    private $onTick;
    private $tickInterval = 5 * 1000;
    private $onStart;
    private $onShutdown;
    private $processNum = 3;
    private $run = false;
    private $backlog = 256;
    private $keywordPath='';

    function __construct()
    {
        $this->tempDir = getcwd();
    }

    /**
     * 设置临时目录
     *
     * @param string $tempDir
     * @return Keyword
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:35
     */
    public function setTempDir(string $tempDir): Keyword
    {
        $this->modifyCheck();
        $this->tempDir = $tempDir;
        return $this;
    }

    /**
     * 设置处理进程数量
     *
     * @param int $num
     * @return Keyword
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:36
     */
    public function setProcessNum(int $num): Keyword
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
     * @return Keyword
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:36
     */
    public function setServerName(string $serverName): Keyword
    {
        $this->modifyCheck();
        $this->serverName = $serverName;
        return $this;
    }

    /**
     * 设置内部定时器的回调方法(用于数据落地)
     *
     * @param $onTick
     * @return Keyword
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:36
     */
    public function setOnTick($onTick): Keyword
    {
        $this->modifyCheck();
        $this->onTick = $onTick;
        return $this;
    }

    /**
     * 设置内部定时器的间隔时间(用于数据落地)
     *
     * @param $tickInterval
     * @return Keyword
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:37
     */
    public function setTickInterval($tickInterval): Keyword
    {
        $this->modifyCheck();
        $this->tickInterval = $tickInterval;
        return $this;
    }

    /**
     * 设置进程启动时的回调(落地数据恢复)
     *
     * @param $onStart
     * @return Keyword
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:37
     */
    public function setOnStart($onStart): Keyword
    {
        $this->modifyCheck();
        $this->onStart = $onStart;
        return $this;
    }

    /**
     * 设置推出前回调(退出时可落地)
     *
     * @param callable $onShutdown
     * @return Keyword
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:37
     */
    public function setOnShutdown(callable $onShutdown): Keyword
    {
        $this->modifyCheck();
        $this->onShutdown = $onShutdown;
        return $this;
    }

    /**
     * 设置关键词文件路径
     *
     * @param string $keywordPath
     * @return Keyword
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午11:30
     */
    public function setKeywordPath(string $keywordPath): Keyword
    {
        $this->modifyCheck();
        $this->keywordPath = $keywordPath;
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
        /** @var $process KeywordProcess*/
        foreach ($list as $process) {
            $server->addProcess($process->getProcess());
        }
    }

    /**
     * 初始化缓存进程
     * @return array
     * @throws \Exception
     */
    public function initProcess(): array
    {
        $this->run = true;
        $array = [];
        for ($i = 1; $i <= $this->processNum; $i++) {
            $config = new KeywordProcessConfig();
            $config->setProcessName("{$this->serverName}.FastCacheProcess.{$i}");
            $config->setSocketFile($this->generateSocketByIndex($i));
            $config->setOnStart($this->onStart);
            $config->setOnShutdown($this->onShutdown);
            $config->setOnTick($this->onTick);
            $config->setTickInterval($this->tickInterval);
            $config->setTempDir($this->tempDir);
            $config->setBacklog($this->backlog);
            $config->setAsyncCallback(false);
            $config->setWorkerIndex($i);
            $array[$i] = new KeywordProcess($config);
        }
        return $array;
    }

    private function generateSocketByIndex($index)
    {
        return $this->tempDir . "/{$this->serverName}.KeywordProcess.{$index}.sock";
    }

    function append($keyword, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $pack = new Package();
        $pack->setCommand($pack::ACTION_APPEND);
        $pack->setKeyword($keyword);
        return $this->sendAndRecv($this->generateSocket(), $pack, $timeout);
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

}
