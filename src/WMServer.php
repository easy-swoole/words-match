<?php


namespace EasySwoole\WordsMatch;


use EasySwoole\Component\Singleton;
use Swoole\Server;

class WMServer
{
    use Singleton;

    private $config;

    function __construct(Config $config)
    {
        $this->config = $config;
    }

    function attachServer(Server $server)
    {

    }

    function load()
    {

    }

    function detect()
    {

    }

    function remove()
    {

    }

    function append()
    {

    }
}