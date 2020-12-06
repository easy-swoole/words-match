<?php

namespace EasySwoole\WordsMatch\Process;

use EasySwoole\Component\Process\Socket\AbstractUnixProcess;
use EasySwoole\WordsMatch\Config;
use EasySwoole\WordsMatch\Dictionary\Dictionary;
use Swoole\Coroutine\Socket;

class DFAProcess extends AbstractUnixProcess
{

    private $reload = false;
    private $dfa = null;

    public function run($args)
    {
        /** @var $config Config*/
        $config = $args;
        ini_set('memory_limit', $config->getMaxMEM());
        $this->dfa = new Dictionary();
        $this->dfa->load($config->getDict());
        $this->addTick(1000, function () use($config) {
            if ($this->reload)
            {
                $temp = new Dictionary();
                $temp->load($config->getDict());
                unset($this->dfa);
                $this->dfa = $temp;
                //主动调用一次gc。清除dfa中的循环引用。
                gc_collect_cycles();
                $this->reload = false;
            }
        });

        parent::run($this->getConfig());
    }

    function onAccept(Socket $socket)
    {
        $replyPackage = null;
        $header = $socket->recvAll(4,1);
        if(strlen($header) != 4){
            $socket->sendAll(Protocol::pack(serialize(Command::ERROR_TIMEOUT)));
            $socket->close();
            return;
        }
        $allLength = Protocol::packDataLength($header);
        $data = $socket->recvAll($allLength,1);
        if(strlen($data) == $allLength){
            try{
                $command = unserialize($data);
                if($command instanceof Command){
                    $args = $command->getArgs();
                    switch ($command->getCommand())
                    {
                        case Command::COMMAND_DETECT:
                            if($this->reload){
                                $replyPackage = Command::ERROR_DICTIONARY_NOT_READY;
                            }else{
                                $replyPackage = $this->dfa->detect($args);
                            }
                            break;
                        case Command::COMMAND_RELOAD:
                            $this->reload = true;
                            $replyPackage = true;
                            break;
                    }
                }else{
                    $replyPackage = Command::ERROR_PACKAGE_ERROR;
                    $socket->close();
                }
            }catch (\Throwable $exception){
                $replyPackage = Command::ERROR_WORKER_ERROR;
                throw $exception;
            } finally {
                $socket->sendAll(Protocol::pack(serialize($replyPackage)));
                $socket->close();
            }
        }else{
            $socket->sendAll(Protocol::pack(serialize(Command::ERROR_TIMEOUT)));
            $socket->close();
        }
    }
}
