<?php
/**
 * @CreateTime:   2019/10/21 下午10:21
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  此进程只负责检测
 */
namespace EasySwoole\WordsMatch;

use EasySwoole\WordsMatch\Config\Config;
use Swoole\Coroutine\Socket;
use EasySwoole\Spl\SplFileStream;
use EasySwoole\WordsMatch\Config\WordsMatchConfig;
use EasySwoole\WordsMatch\Exception\RuntimeError;
use EasySwoole\WordsMatch\Extend\Protocol\Package;
use EasySwoole\WordsMatch\Extend\Protocol\Protocol;
use EasySwoole\Component\Process\Socket\AbstractUnixProcess;

class WordsMatchProcess extends AbstractUnixProcess
{

    private $uuid;

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
            if (!file_exists(Config::WORDSMATCH_SERIALIZE))
            {
                return;
            }
            $splFileStream = new SplFileStream(Config::WORDSMATCH_SERIALIZE, 'a+');
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
                $cache['trees'] = unserialize($trees);
                $groupFileStream = new SplFileStream(Config::GROUPS_SERIALIZE, 'a+');
                $groupFileStream->lock(LOCK_EX);
                $groups = $groupFileStream->getContents();
                $cache['groups'] = json_decode($groups, true);
                $splFileStream->unlock(LOCK_UN);
                $this->cache = $cache;
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
        $replayData = [];
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
                    if (empty($this->cache['trees']))
                    {
                        break;
                    }
                    foreach ($wordBanks as $wordBank) {

                        if (!isset($this->cache['trees'][$wordBank])) {
                            continue;
                        }

                        $result = $this->cache['trees'][$wordBank]->search($content);

                        $groups = [];
                        foreach ($result as $key => $item) {
                            $word = $item['word'];
                            $type = $item['other']['type'];
                            if (in_array($type, [Config::WORD_TYPE_COMPOUND, Config::WORD_TYPE_NORMAL_AND_COMPOUND]) && isset($this->cache['groups'][$wordBank][$word])) {
                                $compoundWords = $this->cache['groups'][$wordBank][$word];
                                foreach ($compoundWords as $compoundWord)
                                {
                                    $compoundWordArr = explode(Config::COMPOUND_WORD_SEPARATOR, $compoundWord[0]);
                                    $groups[md5(sort($compoundWordArr, SORT_STRING))] = [
                                        'compound_word' => $compoundWord[0],
                                        'compound_word_arr' => $compoundWordArr,
                                        'other' => explode(WordsMatchConfig::getInstance()->getSeparator(), $compoundWord[1]),
                                        'total' => count($compoundWordArr),
                                        'current' => 0,
                                        'location' => []
                                    ];
                                }
                            }
                        }

                        foreach ($result as $key => $item) {
                            $word = $item['word'];
                            $type = $item['other']['type'];
                            unset($item['other']['type']);
                            if ($type === Config::WORD_TYPE_NORMAL) {
                                $item['type'] = Config::WORD_TYPE_NORMAL;
                                $replayData[$key] = $item;
                                continue;
                            }

                            if ($type === Config::WORD_TYPE_NORMAL_AND_COMPOUND) {
                                $item['type'] = Config::WORD_TYPE_NORMAL;
                                $replayData[$key] = $item;
                            }

                            if (in_array($type, [Config::WORD_TYPE_COMPOUND, Config::WORD_TYPE_NORMAL_AND_COMPOUND])) {
                                foreach ($groups as &$compound) {
                                    if (in_array($word, $compound['compound_word_arr'], false)) {
                                        $compound['current'] += 1;
                                        $compound['location'][] = $item['location'];
                                        if ($compound['total'] === $compound['current']) {
                                            $replayData[] = [
                                                'word' => $compound['compound_word'],
                                                'other' => $compound['other'],
                                                'location' => array_merge(...$compound['location']),
                                                'type' => Config::WORD_TYPE_COMPOUND
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                break;
        }
        return $replayData;
    }

}
