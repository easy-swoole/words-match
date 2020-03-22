<?php
/**
 * @CreateTime:   2019/10/21 下午10:21
 * @Author:       huizhang  <tuzisir@163.com>
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
use EasySwoole\WordsMatch\Extend\SpecialSymbolFilter;
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

        if (!empty($this->config->getDefaultWordBank())) {
            $this->generateTree(
                $this->config->getWordsMatchPath().$this->config->getDefaultWordBank(),
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
        if ($fromPackage instanceof Package) {
            $type = $this->config->getAlgorithmType();
            var_dump($type);
            switch ($type)
            {
                case WordsMatchConfig::AC:
                    $replayData = $this->execAcCommand($fromPackage);
                    break;
                case WordsMatchConfig::DFA:
                    $replayData = $this->execDfaComment($fromPackage);
                    break;
            }
        }
        return $replayData;
    }

    private function execAcCommand(Package $fromPackage)
    {
        $replayData = null;
        switch ($fromPackage->getCommand()) {
            case $fromPackage::ACTION_SEARCH:
                {
                    $word = $fromPackage->getWord();
                    $filterType = $fromPackage->getFilterType();
                    switch ($filterType) {
                        case $fromPackage::FILTER_C:
                            SpecialSymbolFilter::getInstance()->chinese($word);
                            break;
                        case $fromPackage::FILTER_CEN:
                            SpecialSymbolFilter::getInstance()->chineseEnglishNumber($word);
                            break;
                        case $fromPackage::FILTER_EMOJI:
                            SpecialSymbolFilter::getInstance()->filterEmoji($word);
                            break;
                    }
                    $replayData = $this->tree->search($word);
                }
                break;
        }
        return $replayData;
    }

    private function execDfaComment(Package $fromPackage)
    {
        $replayData = null;
        switch ($fromPackage->getCommand()) {
            case $fromPackage::ACTION_APPEND:
                {
                    $word = $fromPackage->getWord();
                    $otherInfo = $fromPackage->getOtherInfo();
                    $this->tree->append($word, $otherInfo);
                }
                break;
            case $fromPackage::ACTION_SEARCH:
                {
                    $word = $fromPackage->getWord();
                    $filterType = $fromPackage->getFilterType();
                    switch ($filterType) {
                        case $fromPackage::FILTER_C:
                            SpecialSymbolFilter::getInstance()->chinese($word);
                            break;
                        case $fromPackage::FILTER_CEN:
                            SpecialSymbolFilter::getInstance()->chineseEnglishNumber($word);
                            break;
                        case $fromPackage::FILTER_EMOJI:
                            SpecialSymbolFilter::getInstance()->filterEmoji($word);
                            break;
                    }
                    $replayData = $this->tree->search($word);
                }
                break;
            case $fromPackage::ACTION_REMOVE:
                {
                    $word = $fromPackage->getWord();
                    $replayData = $this->tree->remove($word);
                }
                break;
            case $fromPackage::ACTION_EXPORT:
                {
                    $wordsMatchPath = $this->config->getWordsMatchPath();
                    $fileName = $fromPackage->getFileName();
                    $fullPath = $wordsMatchPath.$fileName;
                    $dirName = dirname($fullPath);
                    if (!file_exists($dirName)) {
                        @mkdir($dirName, 0777);
                    }
                    $separator = $fromPackage->getSeparator();
                    $nodeTree = $this->tree->getRoot();
                    $file = fopen($fullPath, 'w+');
                    $this->recursiveExportWord($file, $nodeTree, $separator);
                    fclose($file);
                }
                break;
            case $fromPackage::ACTION_IMPORT:
                {
                    $wordsMatchPath = $this->config->getWordsMatchPath();
                    $fileName = $fromPackage->getFileName();
                    $fullPath = $wordsMatchPath.$fileName;
                    $dirName = dirname($fullPath);
                    if (!file_exists($dirName)) {
                        @mkdir($dirName, 0777);
                    }
                    $separator = $fromPackage->getSeparator();
                    $isCover = $fromPackage->getCover();
                    if ($isCover) {
                        $this->tree = new Dfa();
                    }
                    $replayData = $this->generateTree($fullPath, $separator);
                }
                break;
            default:
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

    private function recursiveExportWord($file, $childs, $separator)
    {
        foreach ($childs as $childInfo) {
            if ($childInfo['end']) {
                $other = $childInfo['other'];
                if (empty($other)) {
                    $writeLine = $childInfo['word']."\n";
                } else {
                    array_unshift($other, $childInfo['word']);
                    $writeLine = implode($separator, $other)."\n";
                }
                fwrite($file, $writeLine);
            }
            if (empty($childInfo['child'])) {
                continue;
            }
            $this->recursiveExportWord($file, $childInfo['child'], $separator);
        }
    }
}
