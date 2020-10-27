<?php

namespace EasySwoole\WordsMatch\Dictionary;

class DFA
{
    private $nodeTree = [];

    public function append(string $word, array $otherInfo) :void
    {
        $word = trim($word);
        $childTree = &$this->nodeTree;
        $len = strlen($word);
        for ($i = 0; $i < $len; $i++) {
            $code = NULL;
            $char = NULL;
            $isEnd = false;
            $asciiCode = ord($word[$i]);
            $asciiByteNum = CodeTrans::judgeAsciiByteNum($asciiCode);
            if ($i < $len-($asciiByteNum-1)) {
                for ($cursor=0;$cursor<$asciiByteNum; $cursor++) {
                    $code .= dechex(ord($word[$i+$cursor]));
                    $char .= $word[$i+$cursor];
                }
                $i += ($asciiByteNum-1);
            }
            if ($i === ($len - 1)) {
                $isEnd = true;
            }
            $childTree = &$this->appendWordToTree($childTree, $code, $char, $isEnd, $word, $otherInfo);
        }

        unset($childTree);
    }

    public function search(string $word) : array
    {
        $search = trim($word);
        if (empty($search)) {
            return [];
        }
        $wordChars = CodeTrans::strToChars($word);
        $hitArr = array();
        $tree = &$this->nodeTree;
        $arrLen = count($wordChars);
        $currentIndex = 0;
        for ($i = 0; $i < $arrLen; $i++) {
            if (isset($tree[$wordChars[$i]])) {
                $node = $tree[$wordChars[$i]];
                if ($node['end']) {
                    $key = md5($node['word']);
                    $start = $i - mb_strlen($node['word'])+1;
                    if (isset($hitArr[$key])) {
                        $hitArr[$key]['count'] ++;
                        $hitArr[$key]['location'][] = $start;
                    } else {
                        $hitArr[$key] = array(
                            'word' => $node['word'],
                            'other' => $node['other'],
                            'count' => 1,
                            'location' => [$start]
                        );
                    }
                    if (empty($node['child'])) {
                        $i = $currentIndex;
                        $tree = &$this->nodeTree;
                        $currentIndex++;
                    } else {
                        $tree = &$tree[$wordChars[$i]]['child'];
                    }
                } else {
                    $tree = &$tree[$wordChars[$i]]['child'];
                }
            } else {
                $i = $currentIndex;
                $tree = &$this->nodeTree;
                $currentIndex++;
            }
        }

        unset($tree, $wordChars);
        return array_values($hitArr);
    }

    public function getRoot()
    {
        return $this->nodeTree;
    }

    public function remove($word, $delTree = false): bool
    {
        $word = trim($word);
        $wordChars = CodeTrans::strToChars($word);
        $wordLen = count($wordChars);
        $childTree = &$this->nodeTree;
        $delIndex = array();
        for ($i = 0; $i < $wordLen; $i++) {
            $code = $wordChars[$i];
            if (isset($childTree[$code])) {
                $delIndex[$i] = [
                    'code' => $code,
                    'index' => &$childTree[$code]
                ];
                if ($i === ($wordLen - 1) && !$childTree[$code]['end']) {
                    return false;
                }
                $childTree = &$childTree[$code]['child'];
            } else {
                return false;
            }
        }
        $idx = $wordLen - 1;

        if ($delTree) {
            $delIndex[$idx]['index']['child'] = array();
            return true;
        }

        if (($idx === 0) && count($delIndex[$idx]['index']['child']) === 0) {
            unset($this->nodeTree[$delIndex[$idx]['code']]);
            return true;
        }

        if (count($delIndex[$idx]['index']['child']) > 0) {
            $delIndex[$idx]['index']['end'] = false;
            unset($delIndex[$idx]['index']['other'], $delIndex[$idx]['index']['word']);
            return true;
        }

        for (; $idx >= 0; $idx--) {
            if (count($delIndex[$idx]['index']['child']) > 0) {
                if ($delIndex[$idx]['index']['end'] === true || $delIndex[$idx]['index']['child'] > 1) {
                    $childCode = $delIndex[$idx + 1]['code'];
                    unset($delIndex[$idx]['index']['child'][$childCode]);
                    return true;
                }
            }
        }

        return false;
    }

    private function &appendWordToTree(&$tree, $code, $word, $end, $str, $otherInfo)
    {
        if (!isset($tree[$code])) {
            $tree[$code] = array(
                'end' => $end,
                'child' => array(),
                'value' => $word,
            );
        }
        if ($end) {
            $tree[$code]['end'] = true;
            $tree[$code]['word'] = $str;
            $tree[$code]['other'] = $otherInfo;
        }

        return $tree[$code]['child'];
    }

    public function getTree()
    {
        return $this->nodeTree;
    }
}
