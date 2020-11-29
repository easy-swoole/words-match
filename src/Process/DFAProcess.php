<?php

namespace EasySwoole\WordsMatch\Process;

use EasySwoole\Component\Process\Socket\AbstractUnixProcess;
use EasySwoole\WordsMatch\Config;
use EasySwoole\WordsMatch\Dictionary\Dictionary;
use Swoole\Coroutine\Socket;

class DFAProcess extends AbstractUnixProcess
{

    private $reload = true;

    public function run($args)
    {
        /** @var $config Config*/
        $config = $args;
        ini_set('memory_limit', $config->getMaxMEM());
        $this->addTick(1000, function () use($config) {
            if ($this->reload)
            {
                $this->reload = false;
                Dictionary::getInstance()->load($config->getDict());
            }
        });

        parent::run($this->getConfig());
    }

    function onAccept(Socket $socket)
    {
        $replyPackage = null;
        $header = $socket->recvAll(4,1);
        if(strlen($header) != 4){
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
                            $replyPackage = Dictionary::getInstance()->detect($args);
                            break;
                        case Command::COMMAND_RELOAD:
                            $this->reload = true;
                            break;
                    }
                }else{
                    $socket->close();
                }
            }catch (\Throwable $exception){
                throw $exception;
            } finally {
                $socket->sendAll(Protocol::pack(serialize($replyPackage)));
                $socket->close();
            }
        }else{
            $socket->close();
        }
    }
}
