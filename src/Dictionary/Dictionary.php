<?php

namespace EasySwoole\WordsMatch\Dictionary;

use EasySwoole\Spl\SplFileStream;

class Dictionary
{
    /** @var $tree DFA */
    private $tree;
    private $group;
    private $file;

    private const WORD_TYPE_NORMAL = 1; // 普通词
    private const WORD_TYPE_COMPOUND = 2; // 复合词
    private const WORD_TYPE_NORMAL_AND_COMPOUND = 3; // 既是普通词又在复合词中

    private const SEPARATOR = '※'; // 每行词信息分隔符
    private const COMPOUND_SEPARATOR = '∮'; // 复合词分隔符

    public function load(string $file)
    {
        $tree = new DFA();
        $this->file = $file;
        $splFileStream = new SplFileStream($file, 'r');
        $normalWords = [];
        $compoundWords = [];
        $group = [];
        while (!$splFileStream->eof()) {
            $line = trim(fgets($splFileStream->getStreamResource()));
            if (empty($line)) {
                continue;
            }
            $items = explode(self::SEPARATOR, $line);
            $first = array_shift($items);
            $remark = implode(self::SEPARATOR, $items);
            $words = explode(self::COMPOUND_SEPARATOR, $first);
            $isCompoundWord = count($words) > 1;
            foreach ($words as $word) {
                $other = [];
                if ($isCompoundWord) {
                    $group[$word][] = [$first, $remark];
                    $compoundWords[] = $word;
                    if (array_key_exists($word, $normalWords)) {
                        $other['remark'] = $normalWords[$word];
                        $other['type'] = self::WORD_TYPE_NORMAL_AND_COMPOUND;
                    } else {
                        $other['remark'] = '';
                        $other['type'] = self::WORD_TYPE_COMPOUND;
                    }
                } else {
                    $normalWords[$word] = $remark;
                    $other['remark'] = $remark;
                    if (in_array($word, $compoundWords, false)) {
                        $other['type'] = self::WORD_TYPE_NORMAL_AND_COMPOUND;
                    } else {
                        $other['type'] = self::WORD_TYPE_NORMAL;
                    }
                }
                $tree->append($word, $other);
            }
        }
        $splFileStream->close();
        $this->tree = $tree;
        $this->group = $group;
    }

    public function detect(string $content)
    {
        $detectResult = $this->tree->search($content);

        $compoundWordsInfo = $this->getCompoundWordsInfo($detectResult);

        $hitResult = $this->hitResult($detectResult, $compoundWordsInfo);

        return $this->formatHitResult($hitResult);
    }

    private function formatHitResult(array $hitResult) : array
    {
        $result = [];
        foreach ($hitResult as $item)
        {
            $result[] = new DetectResult($item);
        }
        return $result;
    }

    /**
     * 计算命中结果
     *
     * @param array $detectResult
     * @param array $compoundWordsInfo
     * @return array
     * CreateTime: 2020/10/27 12:22 上午
     */
    private function hitResult(array $detectResult, array $compoundWordsInfo)
    {
        $result = [];
        foreach ($detectResult as $key => $item) {
            $word = $item['word'];
            $type = $item['other']['type'];
            unset($item['other']['type']);
            $other = $item['other'];
            $count = $item['count'];
            $location = $item['location'];

            // 命中的词的类型为普通词则直接命中, 命中的词的类型为普通词and复合词，则普通词先直接命中
            if (in_array($type, [self::WORD_TYPE_NORMAL, self::WORD_TYPE_NORMAL_AND_COMPOUND], false)) {
                $result[$word] = [
                    'word' => $word,
                    'location' => [
                        [
                            'word' => $word,
                            'length' => mb_strlen($word),
                            'location' => $location
                        ]
                    ],
                    'count' => $count,
                    'remark' => $other['remark'],
                    'type' => self::WORD_TYPE_NORMAL,
                ];
            }

            if (in_array($type, [self::WORD_TYPE_COMPOUND, self::WORD_TYPE_NORMAL_AND_COMPOUND])) {
                foreach ($compoundWordsInfo as $compoundKey => $compound) {
                    if (in_array($word, $compound['compound_word_arr'], false)) {
                        $compoundWordsInfo[$compoundKey]['current'] += 1;
                        $compoundWordsInfo[$compoundKey]['location'][$word] = [
                            'word' => $word,
                            'length' => mb_strlen($word),
                            'location' => $location,
                        ];
                    }
                }
            }
        }

        foreach ($compoundWordsInfo as $compound) {
            if ($compound['total'] <= $compound['current']) {
                $result[$compound['compound_word']] = [
                    'word' => $compound['compound_word'],
                    'location' => array_values($compound['location']),
                    'count' => 1,
                    'remark' => $compound['remark'],
                    'type' => self::WORD_TYPE_COMPOUND
                ];
            }
        }
        return $result;
    }

    /**
     * 获取命中的复合词信息
     *
     * @param array $detectResult
     * @return array
     * CreateTime: 2020/10/27 12:15 上午
     */
    private function getCompoundWordsInfo(array $detectResult)
    {
        $result = [];
        foreach ($detectResult as $key => $item) {
            $word = $item['word'];
            $type = $item['other']['type'];
            if (in_array($type, [self::WORD_TYPE_COMPOUND, self::WORD_TYPE_NORMAL_AND_COMPOUND]) && isset($this->group[$word])) {
                $compoundWords = $this->group[$word];
                foreach ($compoundWords as $compoundWord)
                {
                    $compoundWordArr = explode(self::COMPOUND_SEPARATOR, $compoundWord[0]);

                    sort($compoundWordArr, SORT_STRING);
                    $result[md5(implode('', $compoundWordArr))] = [
                        'compound_word' => $compoundWord[0], // 组合词: es※easyswoole
                        'compound_word_arr' => $compoundWordArr, // 组合词数组: [es,easyswoole]
                        'remark' => $compoundWord[1], // 组合词其它信息
                        'total' => count($compoundWordArr), // 组合词大小
                        'current' => 0, // 符合复合词中的普通词的个数，current === total,说明命中了复合词
                        'location' => [] // 复合词中的普通词在内容中的位置
                    ];
                }
            }
        }

        return $result;
    }

}
