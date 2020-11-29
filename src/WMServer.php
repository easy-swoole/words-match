<?php
namespace EasySwoole\WordsMatch;

use EasySwoole\Component\Csp;
use EasySwoole\Component\Process\Socket\UnixProcessConfig;
use EasySwoole\Component\Singleton;
use EasySwoole\WordsMatch\Process\Command;
use EasySwoole\WordsMatch\Process\DFAProcess;
use EasySwoole\WordsMatch\Process\Protocol;
use EasySwoole\WordsMatch\Process\SocketClient;
use Swoole\Server;

class WMServer
{
    use Singleton;

    private $config;
    private $hasAttach = false;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function attachServer(Server $server):bool
    {
        if($this->hasAttach){
            return false;
        }
        for($i = 0;$i < $this->config->getWorkerNum();$i++){
            $config = new UnixProcessConfig();
            $config->setArg($this->config);
            $config->setSocketFile($this->getSock($i));
            $config->setProcessName("WordsMatchWorker.{$i}");
            $config->setProcessGroup('WordsMatchWorker');
            $server->addProcess((new DFAProcess($config))->getProcess());
        }
        $this->hasAttach = true;
        return $this->hasAttach;
    }

    public function detect(string $content, float $timeout = null)
    {
        $command = new Command();
        $command->setCommand(Command::COMMAND_DETECT);
        $command->setArgs($content);
        return $this->send2worker(
            $command
            , random_int(0, $this->config->getWorkerNum()-1)
            , $timeout
        );
    }

    public function reload(float $timeout = null)
    {
        $command = new Command();
        $command->setCommand(Command::COMMAND_RELOAD);
        return $this->broadcast($command, $timeout);
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
        $csp = new Csp($this->config->getWorkerNum());
        for ($i=0;$i < $this->config->getWorkerNum();$i++){
            $csp->add($i, function() use($i,$command,$timeout){
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
        if(is_string($data)){
            return unserialize(Protocol::unpack($data));
        }else{
            return null;
        }
    }
}
