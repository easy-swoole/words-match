<?php
/**
 * @CreateTime:   2019/10/22 下午10:57
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  客户端
 */
namespace EasySwoole\WordsMatch;

use EasySwoole\Component\Singleton;
use EasySwoole\WordsMatch\Base\WordsMatchAbstract;
use EasySwoole\WordsMatch\Config\WordsMatchConfig;
use EasySwoole\WordsMatch\Extend\Protocol\Package;
use EasySwoole\WordsMatch\Extend\Protocol\Protocol;
use EasySwoole\WordsMatch\Extend\Protocol\UnixClient;

class WordsMatchClient extends WordsMatchAbstract
{

    use Singleton;

    /**
     * 检测内容
     *
     * @param string $content
     * @param float $timeout
     * @return array
     */
    public function detect(string $content, float $timeout = 3.0) : array
    {
        $pack = new Package();
        $pack->setCommand($pack::ACTION_SEARCH);
        $pack->setContent($content);
        $res = $this->sendAndRecv($this->generateSocket(), $pack, $timeout);
        if (empty($res)) {
            return [];
        }
        return $res;
    }

    /**
     * 移除词
     *
     * @param string $word
     * @param float $timeout
     */
    public function remove(string $word, float $timeout = 3.0)
    {
        $pack = new Package();
        $pack->setCommand($pack::ACTION_REMOVE);
        $pack->setWord($word);
        for ($i=1;$i<=WordsMatchConfig::getInstance()->getProcessNum();$i++){
            $this->sendAndRecv($this->generateSocketByIndex($i), $pack, $timeout);
        }
    }

    /**
     * 添加词
     *
     * @param string $word
     * @param array $otherInfo
     * @param float $timeout
     */
    public function append(string $word, array $otherInfo=[], float $timeout = 3.0)
    {
        $pack = new Package();
        $pack->setCommand($pack::ACTION_APPEND);
        $pack->setWord($word);
        $pack->setOtherInfo($otherInfo);
        for ($i=1;$i<=WordsMatchConfig::getInstance()->getProcessNum();$i++){
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

}
