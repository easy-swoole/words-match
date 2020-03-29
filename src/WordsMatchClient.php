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
use EasySwoole\WordsMatch\Extend\Protocol\Package;
use EasySwoole\WordsMatch\Extend\Protocol\Protocol;
use EasySwoole\WordsMatch\Extend\Protocol\UnixClient;

class WordsMatchClient extends WordsMatchAbstract
{

    use Singleton;

    public function search(string $content, float $timeout = 1.0)
    {
        $pack = new Package();
        $pack->setCommand($pack::ACTION_SEARCH);
        $pack->setContent($content);
        return $this->sendAndRecv($this->generateSocket(), $pack, $timeout);
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
