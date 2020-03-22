<?php
/**
 * @CreateTime:   2019/10/22 下午10:57
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词客户端
 */
namespace EasySwoole\WordsMatch;

use EasySwoole\Component\Singleton;
use EasySwoole\WordsMatch\Config\WordsMatchConfig;
use EasySwoole\WordsMatch\Extend\Protocol\Package;
use EasySwoole\WordsMatch\Extend\Protocol\Protocol;
use EasySwoole\WordsMatch\Extend\Protocol\UnixClient;

class WordsMatchClient
{

    /** @var $config WordsMatchConfig */
    private $config;

    use Singleton;

    public function __construct()
    {
        $this->config = WordsMatchConfig::getInstance();
    }

    public function append(string $word, array $otherInfo=[], float $timeout = 1.0)
    {
        $pack = new Package();
        $pack->setCommand($pack::ACTION_APPEND);
        $pack->setWord($word);
        $pack->setOtherInfo($otherInfo);
        for ($i=1;$i<=$this->config->getProcessNum();$i++){
            $this->sendAndRecv($this->generateSocketByIndex($i), $pack, $timeout);
        }
    }

    public function remove(string $word, float $timeout = 1.0)
    {
        $pack = new Package();
        $pack->setCommand($pack::ACTION_REMOVE);
        $pack->setWord($word);
        for ($i=1;$i<=$this->config->getProcessNum();$i++){
            $this->sendAndRecv($this->generateSocketByIndex($i), $pack, $timeout);
        }
    }

    public function search(string $word, int $type=0, float $timeout = 1.0)
    {
        $pack = new Package();
        $pack->setCommand($pack::ACTION_SEARCH);
        $pack->setFilterType($type);
        $pack->setWord($word);
        return $this->sendAndRecv($this->generateSocket(), $pack, $timeout);
    }

    public function export(string $fileName, string $separator=',', float $timeout=1.0)
    {
        $pack = new Package();
        $pack->setCommand($pack::ACTION_EXPORT);
        $pack->setFileName($fileName);
        $pack->setSeparator($separator);
        return $this->sendAndRecv($this->generateSocket(), $pack, $timeout);
    }

    public function import(string $fileName, string $separator=',', bool $isCover=false, float $timeout=1.0)
    {
        $pack = new Package();
        $pack->setCommand($pack::ACTION_IMPORT);
        $pack->setFileName($fileName);
        $pack->setSeparator($separator);
        $pack->setCover($isCover);
        for ($i=1;$i<=$this->config->getProcessNum();$i++){
            $this->sendAndRecv($this->generateSocketByIndex($i), $pack, $timeout);
        }
    }

    private function sendAndRecv($socketFile, Package $package, $timeout)
    {
        $client = new UnixClient($socketFile);
        $client->send(Protocol::pack(serialize($package)));
        $ret = $client->recv($timeout);
        if (!empty($ret)) {
            $ret = unserialize(Protocol::unpack((string)$ret));
            if ($ret instanceof Package) {
                return $ret->getValue();
            }

            return $ret;
        }
        return null;
    }

    private function generateSocket(): string
    {
        $index = rand(1, $this->config->getProcessNum());
        return $this->generateSocketByIndex($index);
    }

    private function generateSocketByIndex($index)
    {
        return $this->config->getTempDir() . "/{$this->config->getServerName()}.Process.{$index}.sock";
    }
}
