<?php


namespace EasySwoole\WordsMatch;


use EasySwoole\Component\Csp;
use EasySwoole\Component\Process\Socket\UnixProcessConfig;
use EasySwoole\Component\Singleton;
use EasySwoole\WordsMatch\Process\Command;
use EasySwoole\WordsMatch\Process\DFAProcess;
use EasySwoole\WordsMatch\Process\Protocol;
use EasySwoole\WordsMatch\Process\SocketClient;
use Swoole\Coroutine;
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

    function load(string $dictPath,float $timeout = null)
    {
        $command = new Command();
        $command->setCommand(Command::COMMAND_LOAD);
        $command->setArgs($dictPath);
        return $this->broadcast($command,$timeout);
    }

    function detect(string $word,float $timeout = null)
    {
        //随机一个workerId ，调用send2worker方法
    }

    function remove(string $word,float $timeout = null)
    {
        $command = new Command();
        $command->setCommand(Command::COMMAND_remove);
        $command->setArgs($word);
        return $this->broadcast($command,$timeout);
    }

    function reload(float $timeout = null)
    {
        $command = new Command();
        $command->setCommand(Command::COMMAND_RELOAD);
        return $this->broadcast($command,$timeout);
    }

    function append(string $word,float $timeout = null)
    {
        $command = new Command();
        $command->setCommand(Command::COMMAND_APPEND);
        $command->setArgs($word);
        return $this->broadcast($command,$timeout);
    }

    private function getSock(int $workerId)
    {
        return "{$this->config->getSockDIR()}/words_match.{$workerId}.sock";
    }

    private function broadcast(Command $command,float $timeout = null)
    {
        if($timeout === null){
            $timeout = $this->config->getTimeout();
        }
        $csp = new Csp($this->config->getWorkerNum() + 2);
        for ($i=0;$i < $this->config->getWorkerNum();$i++){
           $csp->add($i,function ()use($i,$command,$timeout){
               return $this->send2worker($command,$i,$timeout);
           });
        }
        return $csp->exec($timeout);
    }

    private function send2worker(Command $command,int $workerId,float $timeout = null)
    {
        if($timeout === null){
            $timeout = $this->config->getTimeout();
        }
        $client = new SocketClient($this->getSock($workerId));
        $client->send(Protocol::pack(serialize($command)));
        $data = $client->recv($timeout);
        if($data){
            return unserialize(Protocol::unpack($data));
        }else{
            return null;
        }
    }
}