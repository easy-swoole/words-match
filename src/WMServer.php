<?php


namespace EasySwoole\WordsMatch;


use EasySwoole\Component\Process\Socket\UnixProcessConfig;
use EasySwoole\Component\Singleton;
use EasySwoole\WordsMatch\Process\Command;
use EasySwoole\WordsMatch\Process\DFAProcess;
use Swoole\Server;

class WMServer
{
    use Singleton;

    private $config;
    private $hasAttach = false;

    function __construct(Config $config)
    {
        $this->config = $config;
    }

    function attachServer(Server $server):bool
    {
        if($this->hasAttach){
            return false;
        }
        for($i = 0;$i < $this->config->getWorkerNum();$i++){
            $config = new UnixProcessConfig();
            $config->setArg($this->config);
            $config->setSocketFile($this->getSock($i));
            $config->setProcessName("WordsMatchWorker.{$i}");
            $config->setProcessGroup("WordsMatchWorker");
            $server->addProcess((new DFAProcess($config))->getProcess());
        }
        $this->hasAttach = true;
        return $this->hasAttach;
    }

    function load(string $dictPath)
    {
        $command = new Command();
        $command->setCommand(Command::COMMAND_LOAD);
        $command->setArgs($dictPath);
    }

    function detect(string $word)
    {

    }

    function remove(string $word)
    {

    }

    function append(string $word)
    {

    }

    private function getSock(int $workerId)
    {
        return "{$this->config->getSockDIR()}/words_match.{$workerId}.sock";
    }

    private function broadcast(Command $command)
    {

    }

    private function send2worker(Command $command,int $workerId)
    {

    }
}