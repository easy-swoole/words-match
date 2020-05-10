<?php
/**
 * @CreateTime:   2019/10/21 下午10:21
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词进程
 */
namespace EasySwoole\WordsMatch;

use EasySwoole\WordsMatch\Exception\RuntimeError;
use Swoole\Coroutine\Socket;
use EasySwoole\WordsMatch\Base\Dfa;
use EasySwoole\WordsMatch\Extend\Protocol\Package;
use EasySwoole\WordsMatch\Config\WordsMatchConfig;
use EasySwoole\WordsMatch\Extend\Protocol\Protocol;
use EasySwoole\Component\Process\Socket\AbstractUnixProcess;

class WordsMatchProcess extends AbstractUnixProcess
{

    /** @var $tree Dfa*/
    private $tree;

    /** @var $config WordsMatchConfig */
    private $wordsMatchConfig;

    public function run($arg)
    {
        $this->wordsMatchConfig = WordsMatchConfig::getInstance();
        ini_set('memory_limit',$this->getConfig()->getMaxMem().'M');

        $this->tree = new Dfa();

        if (file_exists($this->wordsMatchConfig->getWordBank())) {
            $this->generateTree(
                $this->wordsMatchConfig->getWordBank(),
                $this->wordsMatchConfig->getSeparator()
            );
        } else {
            throw new RuntimeError('Please set up word bank correctly！');
        }

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
                    $content = $fromPackage->getContent();
                    $replayData = $this->tree->search($content);
                }
                break;
            case $fromPackage::ACTION_REMOVE:
                {
                    $word = $fromPackage->getWord();
                    $this->tree->remove($word);
                }
                break;
            case $fromPackage::ACTION_APPEND:
                {
                    $word = $fromPackage->getWord();
                    $otherInfo = $fromPackage->getOtherInfo();
                    $this->tree->append($word, $otherInfo);
                }
        }
        return $replayData;
    }

    /**
     * 生成字典树
     *
     * @param $file
     * @param $separator
     * @return bool
     */
    private function generateTree($file, $separator)
    {
        $file = fopen($file, 'ab+');
        if ($file === false) {
            return false;
        }
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) {
                continue;
            }
            $lineArr = explode($separator, $line);
            $word = array_shift($lineArr);
            $this->tree->append($word, $lineArr);
        }
        return true;
    }

}
