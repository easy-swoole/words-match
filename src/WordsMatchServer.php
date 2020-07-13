<?php
/**
 * @CreateTime:   2019/10/21 下午10:29
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  服务端
 */
namespace EasySwoole\WordsMatch;

use swoole_server;
use EasySwoole\Component\Singleton;
use EasySwoole\Component\AtomicManager;
use EasySwoole\WordsMatch\Base\WordsMatchAbstract;
use EasySwoole\WordsMatch\Config\WordsMatchConfig;
use EasySwoole\WordsMatch\Config\WordsMatchProcessConfig;

class WordsMatchServer extends WordsMatchAbstract
{

    use Singleton;

    public function setConfig(array $config) : WordsMatchServer
    {
        WordsMatchConfig::getInstance($config);
        return $this;
    }

    public function attachToServer(swoole_server $server)
    {
        $config = WordsMatchConfig::getInstance();
        AtomicManager::getInstance()->add('process_num', $config->getProcessNum());
        $list = $this->initProcess();
        /** @var $process WordsMatchProcess*/
        foreach ($list as $process) {
            $server->addProcess($process->getProcess());
        }
    }

    private function initProcess(): array
    {
        $array = [];
        $config = WordsMatchConfig::getInstance();
        for ($i = 1; $i <= $config->getProcessNum(); $i++) {
            $processConfig = new WordsMatchProcessConfig();
            $processConfig->setProcessName($config->getServerName().'.Process.'.$i);
            $processConfig->setSocketFile($this->generateSocketByIndex($i));
            $processConfig->setTempDir($config->getTempDir());
            $processConfig->setBacklog($config->getBacklog());
            $processConfig->setAsyncCallback(false);
            $processConfig->setWorkerIndex($i);
            $processConfig->setMaxMem($config->getMaxMem());
            $array[$i] = new WordsMatchProcess($processConfig);
        }
        return $array;
    }

}