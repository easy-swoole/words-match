<?php
/**
 * @CreateTime:   2020/10/21 12:22 上午
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2020) Easyswoole all rights reserved
 * @Description:  将可单测方法迁于此
 */
namespace EasySwoole\WordsMatch;

use EasySwoole\Component\Singleton;
use EasySwoole\Component\WaitGroup;
use EasySwoole\Spl\SplFileStream;
use EasySwoole\WordsMatch\Base\Dfa;
use EasySwoole\WordsMatch\Config\Config;
use EasySwoole\WordsMatch\Config\WordsMatchConfig;
use EasySwoole\WordsMatch\Exception\RuntimeError;
use EasySwoole\WordsMatch\Extend\Protocol\Package;

class Library
{
    use Singleton;

    private $uuid;

    /**
     * 构建词库
     *
     * @param string $separator
     * @throws RuntimeError
     * CreateTime: 2020/10/21 12:41 上午
     */
    public function buildTrees()
    {
        $groups = [];
        $trees = [];
        $wait = new WaitGroup();
        $wordBanks = WordsMatchConfig::getInstance()->getWordBanks();
        foreach ($wordBanks as $key => $wordBank)
        {
            if (file_exists($wordBank)) {
                $wait->add();
                go(function () use ($wait, $key, $wordBank, &$trees, &$groups) {
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
        $normalWords = [];
        $compoundWords = [];
        $group = [];
        $separator = WordsMatchConfig::getInstance()->getSeparator();
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
                    $normalWords[$word] = $lineArr;
                    $other = $lineArr;
                    if (in_array($word, $compoundWords, false))
                    {
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

    /**
     * 添加此
     *
     * @param Package $fromPackage
     * CreateTime: 2020/10/21 12:51 上午
     */
    public function append(Package $fromPackage)
    {
        $wordBankFiles = WordsMatchConfig::getInstance()->getWordBanks();
        foreach ($fromPackage->getWordBanks() as $wordBank) {
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

    /**
     * 移除词
     *
     * @param Package $fromPackage
     * CreateTime: 2020/10/21 12:49 上午
     */
    public function remove(Package $fromPackage)
    {
        $separator = WordsMatchConfig::getInstance()->getSeparator();
        $wordBankFiles = WordsMatchConfig::getInstance()->getWordBanks();
        foreach ($fromPackage->getWordBanks() as $wordBank)
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
    }

    public function detect(Package $fromPackage, $cache)
    {
        $replayData = [];
        $content = $fromPackage->getContent();
        $wordBanks = $fromPackage->getWordBanks();
        if (empty($wordBanks))
        {
            $wordBanks = array_keys(WordsMatchConfig::getInstance()->getWordBanks());
        }

        foreach ($wordBanks as $wordBank) {

            if (!isset($cache['trees'][$wordBank])) {
                continue;
            }

            $result = $cache['trees'][$wordBank]->search($content);

            $groups = [];
            foreach ($result as $key => $item) {
                $word = $item['word'];
                $type = $item['other']['type'];
                if (in_array($type, [Config::WORD_TYPE_COMPOUND, Config::WORD_TYPE_NORMAL_AND_COMPOUND]) && isset($cache['groups'][$wordBank][$word])) {
                    $compoundWords = $cache['groups'][$wordBank][$word];
                    foreach ($compoundWords as $compoundWord)
                    {
                        $compoundWordArr = explode(Config::COMPOUND_WORD_SEPARATOR, $compoundWord[0]);

                        $other = explode(WordsMatchConfig::getInstance()->getSeparator(), $compoundWord[1]);
                        if ($compoundWord[1] === '')
                        {
                            $other = [];
                        }
                        $groups[md5(sort($compoundWordArr, SORT_STRING))] = [
                            'compound_word' => $compoundWord[0],
                            'compound_word_arr' => $compoundWordArr,
                            'other' => $other,
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
                    $replayData[] = $item;
                    continue;
                }

                if ($type === Config::WORD_TYPE_NORMAL_AND_COMPOUND) {
                    $item['type'] = Config::WORD_TYPE_NORMAL;
                    $replayData[] = $item;
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
                                    'count' => 1,
                                    'location' => array_merge(...$compound['location']),
                                    'type' => Config::WORD_TYPE_COMPOUND
                                ];
                            }
                        }
                    }
                }
            }
        }
        return $replayData;
    }

    public function unserializeCache()
    {
        $cache = [];
        if (!file_exists(Config::WORDSMATCH_SERIALIZE))
        {
            return $cache;
        }
        $splFileStream = new SplFileStream(Config::WORDSMATCH_SERIALIZE, 'a+');
        $splFileStream->lock(LOCK_EX);
        $uuid = $splFileStream->read(10);
        if (!is_numeric($uuid))
        {
            $splFileStream->unlock(LOCK_UN);
            return $cache;
        }
        if ($this->uuid !== $uuid)
        {
            $trees = $splFileStream->getContents();
            $cache['trees'] = unserialize($trees);
            $groupFileStream = new SplFileStream(Config::GROUPS_SERIALIZE, 'a+');
            $groupFileStream->lock(LOCK_EX);
            $groups = $groupFileStream->getContents();
            $cache['groups'] = json_decode($groups, true);
            $groupFileStream->unlock(LOCK_UN);
        }
        $this->uuid = $uuid;
        $splFileStream->unlock(LOCK_UN);
        return $cache;
    }

}
