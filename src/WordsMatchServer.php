<?php
/**
 * @CreateTime:   2019/10/21 下午10:29
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词服务端
 */
namespace EasySwoole\WordsMatch;

use EasySwoole\Component\Process\Exception;
use swoole_server;
use EasySwoole\Component\Singleton;
use EasySwoole\WordsMatch\Config\WordsMatchConfig;
use EasySwoole\WordsMatch\Config\WordsMatchProcessConfig;

class WordsMatchServer
{
    use Singleton;

    /** @var $config WordsMatchConfig*/
    private $config;

    public function setConfig(array $config) : WordsMatchServer
    {
        $this->config = WordsMatchConfig::getInstance($config);
        return $this;
    }

    public function attachToServer(swoole_server $server)
    {
        $list = $this->initProcess();
        /** @var $process WordsMatchProcess*/
        foreach ($list as $process) {
            $server->addProcess($process->getProcess());
        }
    }

    private function initProcess(): array
    {
        $array = [];
        for ($i = 1; $i <= $this->config->getProcessNum(); $i++) {
            $processConfig = new WordsMatchProcessConfig();
            $processConfig->setProcessName($this->config->getServerName().'.Process.'.$i);
            $processConfig->setSocketFile($this->generateSocketByIndex($i));
            $processConfig->setTempDir($this->config->getTempDir());
            $processConfig->setBacklog($this->config->getBacklog());
            $processConfig->setAsyncCallback(false);
            $processConfig->setWorkerIndex($i);
            $processConfig->setMaxMem($this->config->getMaxMem());
            try {
                $array[$i] = new WordsMatchProcess($processConfig);
            } catch (Exception $e) {
            }
        }
        return $array;
    }

    private function generateSocketByIndex($index)
    {
        return $this->config->getTempDir() . "/{$this->config->getServerName()}.Process.{$index}.sock";
    }

}
