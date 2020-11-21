<?php


namespace EasySwoole\WordsMatch\Process;


class Command
{
    const COMMAND_LOAD = 0x1;
    const COMMAND_remove = 0x2;
    const COMMAND_APPEND = 0x3;
    const COMMAND_DETECT = 0x4;
    const COMMAND_RELOAD = 0x5;

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