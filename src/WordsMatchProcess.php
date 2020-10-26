<?php
/**
 * @CreateTime:   2019/10/21 下午10:21
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  此进程只负责检测
 */
namespace EasySwoole\WordsMatch;

use Swoole\Coroutine\Socket;
use EasySwoole\WordsMatch\Exception\RuntimeError;
use EasySwoole\WordsMatch\Extend\Protocol\Package;
use EasySwoole\WordsMatch\Extend\Protocol\Protocol;
use EasySwoole\Component\Process\Socket\AbstractUnixProcess;

class WordsMatchProcess extends AbstractUnixProcess
{

    private $cache = [
        'trees' => [],
        'groups' => []
    ];

    /**
     * 启动时执行
     *
     * @param $arg
     * @throws RuntimeError
     * @throws \EasySwoole\Component\Process\Exception
     */
    public function run($arg)
    {
        ini_set('memory_limit',$this->getConfig()->getMaxMem().'M');
        $this->addTick(3000, function () {
            $cache = Library::getInstance()->unserializeCache();
            if (empty($cache))
            {
                return;
            }
            $this->cache = $cache;
        });
        parent::run($this->getConfig());
    }

    public function onAccept(Socket $socket)
    {
        $header = $socket->recvAll(4, 1);
        if (strlen($header) !== 4) {
            $socket->close();
            return;
        }

        $allLength = Protocol::packDataLength($header);
        $data = $socket->recvAll($allLength, 1);
        if (strlen($data) === $allLength) {
            $replyPackage = $this->executeCommand($data);
            $socket->sendAll(Protocol::pack(serialize($replyPackage)));
            $socket->close();
        }

        $socket->close();
    }

    protected function executeCommand(?string $commandPayload)
    {
        /** @var $fromPackage Package*/
        $replayData = [];
        $fromPackage = unserialize($commandPayload);
        switch ($fromPackage->getCommand()) {
            case $fromPackage::ACTION_SEARCH:
                {
                    if (empty($this->cache['trees']))
                    {
                        break;
                    }
                    $replayData = Library::getInstance()->detect($fromPackage, $this->cache);
                }
                break;
        }
        return $replayData;
    }

}
