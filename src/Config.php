<?php


namespace EasySwoole\WordsMatch;


use EasySwoole\Spl\SplBean;

class Config extends SplBean
{
    private $dict;
    private $workerNum = 1;
    private $maxMEM = "512M";
    private $sockDIR = null;

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
}