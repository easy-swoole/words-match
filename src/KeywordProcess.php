<?php
/**
 * @CreateTime:   2019/10/21 下午10:21
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词进程
 */
namespace EasySwoole\Keyword;
use EasySwoole\Component\Process\Socket\AbstractUnixProcess;
use EasySwoole\Keyword\TreeManager;
use Swoole\Coroutine\Socket;
use EasySwoole\Keyword\Protocol;

class KeywordProcess extends AbstractUnixProcess
{

    /** @var $tree TreeManager*/
    private $tree;

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
        /** @var $processConfig KeywordProcessConfig*/
        $processConfig = $this->getConfig();
        ini_set('memory_limit',$processConfig->getMaxMem());
        $this->tree = new TreeManager();
        if (!empty($processConfig->getKeywordPath())) {
            $this->generateTree($processConfig->getKeywordPath());
        }
        parent::run($processConfig);
    }

    /**
     *
     *
     * @param $file
     * CreateTime: 2019/10/21 下午11:33
     */
    private function generateTree($file)
    {
        $file = fopen($file, 'ab+');
        while (!feof($file)) {
            $line = trim(fgets($file));
            $lineArr = explode("\t", $line);
            $keyword = array_shift($lineArr);
            $this->tree->append($keyword, $lineArr);
        }
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
                        $replayData = true;
                        $this->tree->append($fromPackage->getKeyword(), []);
                    }
                    break;
                default:
            }
        }
        return $replayData;
    }
}
