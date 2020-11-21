<?php


namespace EasySwoole\WordsMatch\Dictionary;


class CodeTrans
{
    public static function judgeAsciiByteNum($ascii): int
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

    public static function strToChars($str): array
    {
        $len = strlen($str);
        $chars = [];
        for ($i = 0; $i < $len; $i++) {
            $code = null;
            $asciiCode = ord($str[$i]);
            $asciiByteNum = self::judgeAsciiByteNum($asciiCode);
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