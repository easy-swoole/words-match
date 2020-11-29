<?php


namespace EasySwoole\WordsMatch\Process;


class Command
{
    
    const COMMAND_DETECT = 0x1;
    const COMMAND_RELOAD = 0x2;

    private $command;
    private $args;

    /**
     * @return mixed
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param mixed $command
     */
    public function setCommand($command): void
    {
        $this->command = $command;
    }

    /**
     * @return mixed
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @param mixed $args
     */
    public function setArgs($args): void
    {
        $this->args = $args;
    }
}
