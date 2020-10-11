<?php
/**
 * @CreateTime:   2019/10/21 下午10:21
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词进程
 */
namespace EasySwoole\WordsMatch;

use EasySwoole\WordsMatch\Config\WordsMatchConfig;
use Swoole\Coroutine\Socket;
use EasySwoole\WordsMatch\Base\Dfa;
use EasySwoole\Spl\SplFileStream;
use EasySwoole\WordsMatch\Exception\RuntimeError;
use EasySwoole\WordsMatch\Extend\Protocol\Package;
use EasySwoole\WordsMatch\Extend\Protocol\Protocol;
use EasySwoole\Component\Process\Socket\AbstractUnixProcess;

class WordsMatchProcess extends AbstractUnixProcess
{

    private $uuid;

    private $trees=[];

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
            $file = EASYSWOOLE_ROOT.'/Temp/words-match-serialize';
            $splFileStream = new SplFileStream($file, 'a+');
            $splFileStream->lock(LOCK_EX);
            $uuid = $splFileStream->read(10);
            if (!is_numeric($uuid))
            {
                $splFileStream->unlock(LOCK_UN);
                return;
            }
            if ($this->uuid !== $uuid)
            {
                $trees = $splFileStream->getContents();
                $this->trees = unserialize($trees);
            }
            $splFileStream->unlock(LOCK_UN);
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
        $replayData = null;
        $fromPackage = unserialize($commandPayload);
        switch ($fromPackage->getCommand()) {
            case $fromPackage::ACTION_SEARCH:
                {
                    $replayData = [];
                    $content = $fromPackage->getContent();
                    $wordBanks = $fromPackage->getWordBanks();
                    if (empty($wordBanks))
                    {
                        $wordBanks = array_keys(WordsMatchConfig::getInstance()->getWordBanks());
                    }
                    if (empty($this->trees))
                    {
                        break;
                    }
                    foreach ($wordBanks as $wordBank)
                    {
                        $tree = $this->trees[$wordBank];
                        $result = $tree->search($content);
                        foreach ($result as $key => $item)
                        {
                            $replayData[$key] = $item;
                        }
                    }
                }
                break;
        }
        return $replayData;
    }

}
