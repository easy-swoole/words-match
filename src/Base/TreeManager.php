<?php
/**
 * @CreateTime:   2019-10-14 22:43
 * @Author:       huizhang AbelZhou<tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  关键词字典树
 */

namespace EasySwoole\Keyword\Base;

class TreeManager
{

    protected $nodeTree = [];

    public function append(string $keyword, array $otherInfo)
    {
        $keyword = trim($keyword);
        $childTree = &$this->nodeTree;
        $len = strlen($keyword);
        for ($i = 0; $i < $len; $i++) {
            $code = NULL;
            $word = NULL;
            $isEnd = false;
            $asciiCode = ord($keyword[$i]);
            $asciiByteNum = $this->judgeAsciiByteNum($asciiCode);
            if ($i < $len-($asciiByteNum-1)) {
                for ($cursor=0;$cursor<$asciiByteNum; $cursor++) {
                    $code .= dechex(ord($keyword[$i+$cursor]));
                    $word .= $keyword[$i+$cursor];
                }
                $i += ($asciiByteNum-1);
            }
            if ($i === ($len - 1)) {
                $isEnd = true;
            }
            $childTree = &$this->appendWordToTree($childTree, $code, $word, $isEnd, $keyword, $otherInfo);
        }

        unset($childTree);
    }

    public function search(string $keyword) {
        $search = trim($keyword);
        if (empty($search)) {
            return false;
        }
        $keywordChars = $this->strToChars($keyword);
        $hitArr = array();
        $tree = &$this->nodeTree;
        $arrLen = count($keywordChars);
        $currentIndex = 0;
        for ($i = 0; $i < $arrLen; $i++) {
            if (isset($tree[$keywordChars[$i]])) {
                $node = $tree[$keywordChars[$i]];
                if ($node['end']) {
                    $key = md5($node['keyword']);
                    if (isset($hitArr[$key])) {
                        $hitArr[$key]['count'] ++;
                    } else {
                        $hitArr[$key] = array(
                            'keyword' => $node['keyword'],
                            'other' => $node['other'],
                            'count' => 1
                        );
                    }
                    if (empty($node['child'])) {
                        $i = $currentIndex;
                        $tree = &$this->nodeTree;
                        $currentIndex++;
                    } else {
                        $tree = &$tree[$keywordChars[$i]]['child'];
                    }
                } else {
                    $tree = &$tree[$keywordChars[$i]]['child'];
                }
            } else {
                $i = $currentIndex;
                $tree = &$this->nodeTree;
                $currentIndex++;
            }
        }

        unset($tree, $keywordChars);
        return $hitArr;
    }

    public function getTree()
    {
        return $this->nodeTree;
    }

    public function remove($keyword, $delTree = false): bool
    {
        $keyword = trim($keyword);
        $keywordChars = $this->strToChars($keyword);
        $keywordLen = count($keywordChars);
        $childTree = &$this->nodeTree;
        $delIndex = array();
        for ($i = 0; $i < $keywordLen; $i++) {
            $code = $keywordChars[$i];
            if (isset($childTree[$code])) {
                $delIndex[$i] = [
                    'code' => $code,
                    'index' => &$childTree[$code]
                ];
                if ($i === ($keywordLen - 1) && !$childTree[$code]['end']) {
                    return false;
                }
                $childTree = &$childTree[$code]['child'];
            } else {
                return false;
            }
        }
        $idx = $keywordLen - 1;

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
            unset($delIndex[$idx]['index']['other'], $delIndex[$idx]['index']['keyword']);
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
            $tree[$code]['keyword'] = $str;
            $tree[$code]['other'] = $otherInfo;
        }

        return $tree[$code]['child'];
    }

    public function judgeAsciiByteNum($ascii): int
    {
        $result = 0;
        if (($ascii >> 7) === 0) {
            $result = 1;
        } else if (($ascii >> 4) === 15) {
            $result = 4;
        } else if (($ascii >> 5) === 7) {
            $result = 3;
        } else if (($ascii >> 6) === 3) {
            $result = 2;
        }
        return $result;
    }

    public function strToChars($str): array
    {
        $len = strlen($str);
        $chars = [];
        for ($i = 0; $i < $len; $i++) {
            $code = null;
            $asciiCode = ord($str[$i]);
            $asciiByteNum = $this->judgeAsciiByteNum($asciiCode);
            if ($i < $len-($asciiByteNum-1)) {
                $char = null;
                for ($cursor=0;$cursor<$asciiByteNum; $cursor++) {
                    $char .= dechex(ord($str[$i+$cursor]));
                }
                $chars[] = $char;
                $i += ($asciiByteNum-1);
            }
        }
        return $chars;
    }

}
