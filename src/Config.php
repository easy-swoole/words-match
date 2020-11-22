<?php


namespace EasySwoole\WordsMatch;


use EasySwoole\Spl\SplBean;

class Config extends SplBean
{
    private $dict;
    private $workerNum = 1;
    private $maxMEM = "512M";
    private $sockDIR = null;
    private $timeout = 3.0;

    protected function initialize(): void
    {
        if(empty($this->sockDIR)){
            $this->sockDIR = sys_get_temp_dir();
        }
    }

    /**
     * @return mixed
     */
    public function getDict()
    {
        return $this->dict;
    }

    /**
     * @param mixed $dict
     */
    public function setDict($dict): void
    {
        $this->dict = $dict;
    }

    /**
     * @return mixed
     */
    public function getWorkerNum()
    {
        return $this->workerNum;
    }

    /**
     * @param mixed $workerNum
     */
    public function setWorkerNum($workerNum): void
    {
        $this->workerNum = $workerNum;
    }

    /**
     * @return string
     */
    public function getMaxMEM(): string
    {
        return $this->maxMEM;
    }

    /**
     * @param string $maxMEM
     */
    public function setMaxMEM(string $maxMEM): void
    {
        $this->maxMEM = $maxMEM;
    }

    /**
     * @return null
     */
    public function getSockDIR()
    {
        return $this->sockDIR;
    }

    /**
     * @param null $sockDIR
     */
    public function setSockDIR($sockDIR): void
    {
        $this->sockDIR = $sockDIR;
    }

    /**
     * @return float
     */
    public function getTimeout(): float
    {
        return $this->timeout;
    }

    /**
     * @param float $timeout
     */
    public function setTimeout(float $timeout): void
    {
        $this->timeout = $timeout;
    }


}