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

    private $trees=[];

    /** @var $config WordsMatchConfig */
    private $wordsMatchConfig;

    public function run($arg)
    {
        $this->wordsMatchConfig = WordsMatchConfig::getInstance();
        ini_set('memory_limit',$this->getConfig()->getMaxMem().'M');

        $this->buildTrees();

        parent::run($this->getConfig());
    }

    /**
     * 构建多词库
     *
     * @throws RuntimeError
     */
    private function buildTrees()
    {
        $wordBank = $this->wordsMatchConfig->getWordBank();
        if (is_array($wordBank))
        {
            foreach ($this->wordsMatchConfig->getWordBank() as $key => $item)
            {
                if (file_exists($item)) {
                    $this->trees[$key] = $this->generateTree($item);
                } else {
                    throw new RuntimeError('Please set up word bank correctly！');
                }

            }
        } else if (is_string($wordBank)) {
            if (file_exists($wordBank)) {
                $this->trees['default'] = $this->generateTree($wordBank);
            } else {
                throw new RuntimeError('Please set up word bank correctly！');
            }
        } else {
            throw new RuntimeError("WordBank's configuration error!");
        }
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
        $wordBankName = $fromPackage->getWordBankName();
        if (!isset($this->trees[$wordBankName]))
        {
            return $replayData;
        }
        /** @var $tree Dfa*/
        $tree = $this->trees[$wordBankName];
        switch ($fromPackage->getCommand()) {
            case $fromPackage::ACTION_SEARCH:
                {
                    $content = $fromPackage->getContent();
                    $replayData = $tree->search($content);
                }
                break;
            case $fromPackage::ACTION_REMOVE:
                {
                    $word = $fromPackage->getWord();
                    $tree->remove($word);
                }
                break;
            case $fromPackage::ACTION_APPEND:
                {
                    $word = $fromPackage->getWord();
                    $otherInfo = $fromPackage->getOtherInfo();
                    $tree->append($word, $otherInfo);
                }
        }
        return $replayData;
    }

    /**
     * 生成字典树
     *
     * @param $file
     * @return bool
     */
    private function generateTree($file)
    {
        $file = fopen($file, 'ab+');
        if ($file === false) {
            throw new RuntimeError("fopen $file fail!");
        }
        $tree = new Dfa();
        $separator = $this->wordsMatchConfig->getSeparator();
        while (!feof($file)) {
            $line = trim(fgets($file));
            if (empty($line)) {
                continue;
            }
            $lineArr = explode($separator, $line);
            $word = array_shift($lineArr);
            $tree->append($word, $lineArr);
        }

        return $tree;
    }

}
