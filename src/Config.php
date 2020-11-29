<?php


namespace EasySwoole\WordsMatch;


use EasySwoole\Component\Singleton;
use EasySwoole\Spl\SplBean;

class Config extends SplBean
{
    use Singleton;

    private $dict;
    private $workerNum = 1;
    private $maxMEM = '512M';
    private $timeout = 3.0;
    private $sockDIR;

    protected function initialize(): void
    {
        if(empty($this->sockDIR)){
            $this->sockDIR = sys_get_temp_dir();
        }
    }

    public function setDict(string $dict)
    {
        $this->dict = $dict;
        return $this;
    }

    public function getDict()
    {
        return $this->dict;
    }

    public function getWorkerNum()
    {
        return $this->workerNum;
    }

    public function setWorkerNum($workerNum)
    {
        $this->workerNum = $workerNum;
        return $this;
    }

    public function getMaxMEM(): string
    {
        return $this->maxMEM;
    }

    public function setMaxMEM(string $maxMEM)
    {
        $this->maxMEM = $maxMEM;
        return $this;
    }

    public function getSockDIR()
    {
        return $this->sockDIR;
    }

    public function setSockDIR($sockDIR)
    {
        $this->sockDIR = $sockDIR;
        return $this;
    }

    public function getTimeout(): float
    {
        return $this->timeout;
    }

    public function setTimeout(float $timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

}
