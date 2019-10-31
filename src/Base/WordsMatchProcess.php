<?php
/**
 * @CreateTime:   2019/10/21 下午10:21
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词进程
 */
namespace EasySwoole\WordsMatch\Base;
use EasySwoole\Component\Process\Socket\AbstractUnixProcess;
use EasySwoole\WordsMatch\Exception\RuntimeError;
use EasySwoole\WordsMatch\Base\TreeManager;
use Swoole\Coroutine\Socket;
use EasySwoole\WordsMatch\Base\Protocol;
use EasySwoole\WordsMatch\Base\Package;

class WordsMatchProcess extends AbstractUnixProcess
{

    /** @var $tree TreeManager*/
    private $tree;

    /** @var $config WordsMatchProcessConfig */
    private $config;

    /**
     * 关键词服务运行
     *
     * @param $arg
     * @throws \EasySwoole\Component\Process\Exception
     * CreateTime: 2019/10/21 下午10:56
     */
    public function run($arg)
    {
        // TODO: Implement run() method.
        $this->config = $this->getConfig();
        ini_set('memory_limit',$this->config->getMaxMem());
        $this->tree = new TreeManager();
        if (!empty($this->config->getDefaultWordBank())) {
            $this->generateTree($this->config->getDefaultWordBank(), $this->config->getSeparator());
        }
        parent::run($this->config);
    }

    function onAccept(Socket $socket)
    {
        // TODO: Implement onAccept() method.
        // 收取包头4字节计算包长度 收不到4字节包头丢弃该包
        $header = $socket->recvAll(4, 1);
        if (strlen($header) != 4) {
            $socket->close();
            return;
        }

        // 收包头声明的包长度 包长一致进入命令处理流程
        $allLength = Protocol::packDataLength($header);
        $data = $socket->recvAll($allLength, 1);
        if (strlen($data) == $allLength) {
            $replyPackage = $this->executeCommand($data);
            $socket->sendAll(Protocol::pack(serialize($replyPackage)));
            $socket->close();
        }

        // 否则丢弃该包不进行处理
        $socket->close();
        return;
    }

    protected function executeCommand(?string $commandPayload)
    {
        $replayData = null;
        $fromPackage = unserialize($commandPayload);
        if ($fromPackage instanceof Package) {
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
                        $exportPath = $this->config->getExportPath();
                        if (empty($exportPath)) {
                            $exportPath = $this->config->getDefaultPath();
                        }
                        if (!file_exists($exportPath)) {
                            @mkdir($exportPath, 0777);
                        }
                        $fileName = $fromPackage->getFileName();
                        $separator = $fromPackage->getSeparator();
                        $nodeTree = $this->tree->getTree();
                        $file = fopen($exportPath.$fileName, 'w+');
                        $this->recursiveExportWord($file, $nodeTree, $separator);
                        fclose($file);
                    }
                    break;
                case $fromPackage::ACTION_IMPORT:
                    {
                        $replayData = true;
                        $importPath = $this->config->getImportPath();
                        if (empty($exportPath)) {
                            $exportPath = $this->config->getDefaultPath();
                        }
                        if (!file_exists($exportPath)) {
                            return false;
                        }
                        $fileName = $fromPackage->getFileName();
                        $separator = $fromPackage->getSeparator();
                        $isCover = $fromPackage->getCover();
                        if ($isCover) {
                            $this->tree = new TreeManager();
                        }
                        $this->generateTree($importPath.$fileName, $separator);
                    }
                    break;
                default:
            }
        }
        return $replayData;
    }

    /**
     * 生成字典树
     *
     * @param $file
     * @param $separator
     * CreateTime: 2019/10/21 下午11:33
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
    }

    private function recursiveExportWord($file, $childs, $separator)
    {
        foreach ($childs as $childInfo) {
            if ($childInfo['end']) {
                $other = $childInfo['other'];
                $lineArr = [];
                $writeLine='';
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
