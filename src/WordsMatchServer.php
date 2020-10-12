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
use EasySwoole\WordsMatch\Config\Config;
use EasySwoole\WordsMatch\Base\WordsMatchAbstract;
use EasySwoole\WordsMatch\Config\WordsMatchConfig;
use EasySwoole\WordsMatch\Config\WordsMatchProcessConfig;
use EasySwoole\Component\Process\Socket\UnixProcessConfig;

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
            $processConfig->setProcessGroup('words-match');
            $processConfig->setProcessName($config->getServerName().'.Tasker.'.$i);
            $processConfig->setSocketFile($this->generateSocketByIndex($i));
            $processConfig->setTempDir($config->getTempDir());
            $processConfig->setBacklog($config->getBacklog());
            $processConfig->setAsyncCallback(false);
            $processConfig->setWorkerIndex($i);
            $processConfig->setMaxMem($config->getMaxMem());
            $array[] = new WordsMatchProcess($processConfig);
        }

        $processConfig= new UnixProcessConfig();
        $processConfig->setProcessGroup('words-match');
        $processConfig->setProcessName('words-match-manager');
        $processConfig->setSocketFile(Config::MANAGER_PROCESS_SOCK);
        $array[] = new ManagerProcess($processConfig);
        return $array;
    }

}
