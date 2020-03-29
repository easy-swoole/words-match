<?php
/**
 * @CreateTime:   2019/10/21 下午10:21
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词进程
 */
namespace EasySwoole\WordsMatch;

use Swoole\Coroutine\Socket;
use EasySwoole\WordsMatch\Algorithm\Ac\Ac;
use EasySwoole\WordsMatch\Algorithm\Dfa\Dfa;
use EasySwoole\WordsMatch\Base\AlgorithmInter;
use EasySwoole\WordsMatch\Extend\Protocol\Package;
use EasySwoole\WordsMatch\Config\WordsMatchConfig;
use EasySwoole\WordsMatch\Extend\Protocol\Protocol;
use EasySwoole\Component\Process\Socket\AbstractUnixProcess;

class WordsMatchProcess extends AbstractUnixProcess
{

    /** @var $tree AlgorithmInter*/
    private $tree;

    /** @var $config WordsMatchConfig */
    private $config;

    public function run($arg)
    {
        $this->config = WordsMatchConfig::getInstance();
        ini_set('memory_limit',$this->config->getMaxMem());

        switch ($this->config->getAlgorithmType())
        {
            case WordsMatchConfig::AC:
                $this->tree = new Ac();
                break;
            case WordsMatchConfig::DFA:
                $this->tree = new Dfa();
                break;
        }

        if (!empty($this->config->getWordBank())) {
            $this->generateTree(
                $this->config->getWordBank(),
                $this->config->getSeparator()
            );
        }

        parent::run($this->config);
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
        }
        return $replayData;
    }

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
        $this->tree->prepare();
        return true;
    }

}
