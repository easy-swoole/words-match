<?php
/**
 * @CreateTime:   2020/10/10 11:22 下午
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2020) Easyswoole all rights reserved
 * @Description:  进程管理
 */
namespace EasySwoole\WordsMatch;

use Swoole\Coroutine\Socket;
use EasySwoole\Trigger\Location;
use EasySwoole\EasySwoole\Trigger;
use EasySwoole\Component\Process\Socket\AbstractUnixProcess;
use EasySwoole\Component\Process\Socket\UnixProcessConfig;
use EasySwoole\WordsMatch\Extend\Protocol\Package;
use EasySwoole\WordsMatch\Extend\Protocol\Protocol;
use EasySwoole\WordsMatch\Config\WordsMatchConfig;

class ManagerProcess extends AbstractUnixProcess {

    private $actionQueue;
    private $wordBanksMd5 = [];

    public function __construct(UnixProcessConfig $config)
    {
        ini_set('memory_limit','4096M');
        $this->actionQueue = new \SplQueue();
        parent::__construct($config);
    }

    public function run($arg)
    {
        foreach (WordsMatchConfig::getInstance()->getWordBanks() as $key => $item)
        {
            $this->wordBanksMd5[$key] = md5_file($item);
        }

        Library::getInstance()->buildTrees();

        // 监听文件变化重新生成词库
        $this->addTick(3000, function () {
            $wordBanks = WordsMatchConfig::getInstance()->getWordBanks();
            foreach ($this->wordBanksMd5 as $key => $item)
            {
                $md5file = md5_file($wordBanks[$key]);
                if ($item !== $md5file)
                {
                    $this->wordBanksMd5[$key] = $md5file;
                    Library::getInstance()->buildTrees();
                    break;
                }
            }
        });

        // 监听对词库的操作
        $this->addTick(1000, function () {
            $queueCount = $this->actionQueue->count();
            if ($queueCount === 0)
            {
                return;
            }
            for ($i=0;$i<$queueCount;$i++)
            {
                $commandPayload = $this->actionQueue->dequeue();
                /** @var $fromPackage Package*/
                $fromPackage = unserialize($commandPayload);
                switch ($fromPackage->getCommand()) {
                    case $fromPackage::ACTION_REMOVE:
                        Library::getInstance()->remove($fromPackage);
                        break;
                    case $fromPackage::ACTION_APPEND:
                        Library::getInstance()->append($fromPackage);
                        break;
                }
            }
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
        $this->actionQueue->enqueue($commandPayload);
    }

    public function onException(\Throwable $throwable, ...$args)
    {
        $location = new Location();
        $location->setFile($throwable->getFile());
        $location->setLine($throwable->getLine());
        Trigger::getInstance()->error($throwable->getMessage(), $throwable->getCode(), $location);
    }
}
