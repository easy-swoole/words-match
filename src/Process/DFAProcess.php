<?php


namespace EasySwoole\WordsMatch\Process;


use EasySwoole\Component\Process\Socket\AbstractUnixProcess;
use Swoole\Coroutine\Socket;

class DFAProcess extends AbstractUnixProcess
{
    function onAccept(Socket $socket)
    {
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

                }else{
                    $socket->close();
                }
            }catch (\Throwable $exception){
                throw $exception;
            } finally {
                $socket->close();
            }
        }else{
            $socket->close();
        }
    }
}