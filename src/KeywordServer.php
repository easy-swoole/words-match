<?php
/**
 * @CreateTime:   2019/10/21 下午10:29
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词服务端
 */
namespace EasySwoole\Keyword;

use swoole_server;
use EasySwoole\Component\Singleton;
use EasySwoole\Keyword\Base\Protocol;
use EasySwoole\Keyword\Base\UnixClient;
use EasySwoole\Keyword\Base\KeywordProcess;
use EasySwoole\Keyword\Base\KeywordClientInter;
use EasySwoole\Keyword\Base\KeywordProcessConfig;
use EasySwoole\Keyword\Exception\RuntimeError;

class KeywordServer implements KeywordClientInter
{
    use Singleton;

    private $tempDir;
    private $serverName = 'EasySwoole';
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
     * @return KeywordServer
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:35
     */
    public function setTempDir(string $tempDir): KeywordServer
    {
        $this->modifyCheck();
        $this->tempDir = $tempDir;
        return $this;
    }

    /**
     * 设置处理进程数量
     *
     * @param int $num
     * @return KeywordServer
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:36
     */
    public function setProcessNum(int $num): KeywordServer
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
     * @return KeywordServer
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:36
     */
    public function setServerName(string $serverName): KeywordServer
    {
        $this->modifyCheck();
        $this->serverName = $serverName;
        return $this;
    }

    /**
     * 设置内部定时器的回调方法(用于数据落地)
     *
     * @param $onTick
     * @return KeywordServer
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午10:36
     */
    public function setOnTick($onTick): KeywordServer
    {
        $this->modifyCheck();
        $this->onTick = $onTick;
        return $this;
    }

    /**
     * 设置关键词文件路径
     *
     * @param string $keywordPath
     * @return KeywordServer
     * @throws RuntimeError
     * CreateTime: 2019/10/21 下午11:30
     */
    public function setKeywordPath(string $keywordPath): KeywordServer
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
    private function initProcess(): array
    {
        $this->run = true;
        $array = [];
        for ($i = 1; $i <= $this->processNum; $i++) {
            $config = new KeywordProcessConfig();
            $config->setProcessName("{$this->serverName}.Process.{$i}");
            $config->setSocketFile($this->generateSocketByIndex($i));
            $config->setTempDir($this->tempDir);
            $config->setBacklog($this->backlog);
            $config->setAsyncCallback(false);
            $config->setWorkerIndex($i);
            $config->setKeywordPath($this->keywordPath);
            $array[$i] = new KeywordProcess($config);
        }
        return $array;
    }

    private function generateSocketByIndex($index)
    {
        return $this->tempDir . "/{$this->serverName}.KeywordProcess.{$index}.sock";
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

    public function append($keyword, $otherInfo=[], float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $pack = new Package();
        $pack->setCommand($pack::ACTION_APPEND);
        $pack->setKeyword($keyword);
        $pack->setOtherInfo($otherInfo);
        for ($i=1;$i<=$this->processNum;$i++){
            $this->sendAndRecv($this->generateSocketByIndex($i), $pack, $timeout);
        }
        return true;
    }

    public function remove($keyword, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $pack = new Package();
        $pack->setCommand($pack::ACTION_REMOVE);
        $pack->setKeyword($keyword);
        for ($i=1;$i<=$this->processNum;$i++){
            $this->sendAndRecv($this->generateSocketByIndex($i), $pack, $timeout);
        }
        return true;
    }

    public function search($keyword, float $timeout = 1.0)
    {
        if ($this->processNum <= 0) {
            return false;
        }
        $pack = new Package();
        $pack->setCommand($pack::ACTION_SEARCH);
        $pack->setKeyword($keyword);
        return $this->sendAndRecv($this->generateSocket(), $pack, $timeout);
    }

}
