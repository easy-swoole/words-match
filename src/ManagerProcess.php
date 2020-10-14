<?php
/**
 * @CreateTime:   2020/10/10 11:22 下午
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2020) Easyswoole all rights reserved
 * @Description:  进程管理
 */
namespace EasySwoole\WordsMatch;

use EasySwoole\Component\WaitGroup;
use EasySwoole\EasySwoole\Trigger;
use EasySwoole\Trigger\Location;
use EasySwoole\WordsMatch\Config\Config;
use Swoole\Coroutine\Socket;
use EasySwoole\Spl\SplFileStream;
use EasySwoole\WordsMatch\Base\Dfa;
use EasySwoole\Component\Process\Socket\AbstractUnixProcess;
use EasySwoole\Component\Process\Socket\UnixProcessConfig;
use EasySwoole\WordsMatch\Extend\Protocol\Package;
use EasySwoole\WordsMatch\Extend\Protocol\Protocol;
use EasySwoole\WordsMatch\Config\WordsMatchConfig;
use EasySwoole\WordsMatch\Exception\RuntimeError;

class ManagerProcess extends AbstractUnixProcess {

    private $actionQueue;
    private $wordBanksMd5 = [];

    public function __construct(UnixProcessConfig $config)
    {
        ini_set('memory_limit','4048M');
        $this->actionQueue = new \SplQueue();
        parent::__construct($config);
    }

    public function run($arg)
    {
        $this->buildTrees();
        $wordBanks = WordsMatchConfig::getInstance()->getWordBanks();

        // 监听文件变化重新生成词库
        $this->addTick(3000, function () use ($wordBanks) {
            foreach ($this->wordBanksMd5 as $key => $item)
            {
                if ($item !== md5_file($wordBanks[$key]))
                {
                    $this->buildTrees();
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
                $replayData = null;
                $fromPackage = unserialize($commandPayload);
                $wordBanks = $fromPackage->getWordBanks();
                $wordBankFiles = WordsMatchConfig::getInstance()->getWordBanks();
                $separator = WordsMatchConfig::getInstance()->getSeparator();
                switch ($fromPackage->getCommand()) {
                    case $fromPackage::ACTION_REMOVE:
                        foreach ($wordBanks as $wordBank)
                        {
                            $wordBankFile = $wordBankFiles[$wordBank];
                            $splFileStream = new SplFileStream($wordBankFile, 'r');
                            $splFileStream->lock(LOCK_EX);
                            $content = '';
                            while (!$splFileStream->eof())
                            {
                                $line = trim(fgets($splFileStream->getStreamResource()));
                                if (empty($line)) {
                                    continue;
                                }
                                $lineArr = explode($separator, $line);
                                $word = array_shift($lineArr);
                                if ($word === $fromPackage->getWord())
                                {
                                    continue;
                                }
                                $content .= $line.PHP_EOL;
                            }
                            $splFileStream->unlock(LOCK_UN);
                            $splFileStream->close();
                            $splFileStream = new SplFileStream($wordBankFile, 'w');
                            $splFileStream->lock(LOCK_EX);
                            $splFileStream->write($content);
                            $splFileStream->unlock(LOCK_UN);
                            $splFileStream->close();
                        }
                        break;
                    case $fromPackage::ACTION_APPEND:
                        {
                            foreach ($wordBanks as $wordBank) {
                                $wordBankFile = $wordBankFiles[$wordBank];
                                $splFileStream = new SplFileStream($wordBankFile, 'a+');
                                $splFileStream->lock(LOCK_EX);
                                $separator = WordsMatchConfig::getInstance()->getSeparator();
                                $row = $fromPackage->getWord();
                                while (!$splFileStream->eof())
                                {
                                    $line = trim(fgets($splFileStream->getStreamResource()));
                                    if (empty($line)) {
                                        continue;
                                    }
                                    $lineArr = explode($separator, $line);
                                    $word = array_shift($lineArr);
                                    if ($word === $fromPackage->getWord())
                                    {
                                        return;
                                    }
                                }
                                if (!empty($fromPackage->getOtherInfo())) {
                                    $row .= $separator . implode($separator, $fromPackage->getOtherInfo());
                                }
                                $row .= PHP_EOL;
                                $splFileStream->write($row);
                                $splFileStream->unlock(LOCK_UN);
                                $splFileStream->close();
                            }
                        }
                        break;
                }
            }
        });

        parent::run($this->getConfig());
    }

    private function buildTrees()
    {
        $groups = [];
        $trees = [];
        $wordBanks = WordsMatchConfig::getInstance()->getWordBanks();
        $wait = new WaitGroup();
        foreach ($wordBanks as $key => $wordBank)
        {
            if (file_exists($wordBank)) {
                $wait->add();
                go(function () use ($wait, $key, $wordBank, &$trees, &$groups) {
                    $this->wordBanksMd5[$key] = md5_file($wordBank);
                    [$tree, $group] = $this->buildTree($wordBank);
                    $trees[$key] = $tree;
                    $groups[$key] = $group;
                    $wait->done();
                });
            } else {
                throw new RuntimeError('Please set up word bank correctly！');
            }
        }
        $wait->wait();

        $groupsSerialize = json_encode($groups, JSON_UNESCAPED_UNICODE);
        $splFileStream = new SplFileStream(Config::GROUPS_SERIALIZE, 'w');
        $splFileStream->lock(LOCK_EX);
        $splFileStream->write($groupsSerialize);
        $splFileStream->unlock(LOCK_UN);
        $splFileStream->close();

        $treesSerialize = serialize($trees);
        $splFileStream = new SplFileStream(Config::WORDSMATCH_SERIALIZE, 'w');
        $splFileStream->lock(LOCK_EX);
        $splFileStream->write(time().$treesSerialize);
        $splFileStream->unlock(LOCK_UN);
        $splFileStream->close();

    }

    private function buildTree($file)
    {
        $splFileStream = new SplFileStream($file, 'r');
        $splFileStream->lock(LOCK_EX);
        $tree = new Dfa();
        $separator = WordsMatchConfig::getInstance()->getSeparator();
        $normalWords = [];
        $compoundWords = [];
        $group = [];
        while (!$splFileStream->eof()) {
            $line = trim(fgets($splFileStream->getStreamResource()));
            if (empty($line)) {
                continue;
            }
            $lineArr = explode($separator, $line);
            $first = array_shift($lineArr);
            $words = explode(Config::COMPOUND_WORD_SEPARATOR, $first);
            $isCompoundWord = count($words) > 1;
            foreach ($words as $word)
            {
                $other = [];
                if ($isCompoundWord)
                {
                    $group[$word][] = [
                        $first,
                        implode(',', $lineArr)
                    ];
                    $compoundWords[] = $word;
                    if (array_key_exists($word, $normalWords))
                    {
                        $other = $normalWords[$word];
                        $other['type'] = Config::WORD_TYPE_NORMAL_AND_COMPOUND;
                    } else {
                        $other['type'] = Config::WORD_TYPE_COMPOUND;
                    }
                } else {
                    $normalWords[] = $word;
                    if (in_array($word, $compoundWords, false))
                    {
                        $other = $lineArr;
                        $other['type'] = Config::WORD_TYPE_NORMAL_AND_COMPOUND;
                    } else {
                        $other['type'] = Config::WORD_TYPE_NORMAL;
                    }
                }
                $tree->append($word, $other);
            }
        }
        $splFileStream->unlock(LOCK_UN);
        $splFileStream->close();
        return [$tree, $group];
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
