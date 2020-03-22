<?php
/**
 * @CreateTime:   2020-03-22 15:40
 * @Author:       huizhang <tuzisir@163.com>
 * @Copyright:    copyright(2020) Easyswoole all rights reserved
 * @Description:  编码转换
 */
namespace EasySwoole\WordsMatch\Extend;

use EasySwoole\Component\Singleton;

class CodeTrans
{
    use Singleton;

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