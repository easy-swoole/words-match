<?php


namespace EasySwoole\WordsMatch\Process;


use EasySwoole\Component\Process\Socket\AbstractUnixProcess;
use Swoole\Coroutine\Socket;

class DFAProcess extends AbstractUnixProcess
{
    function onAccept(Socket $socket)
    {

    }
}