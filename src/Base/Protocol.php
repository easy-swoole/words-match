<?php
/**
 * @CreateTime:   2019/10/21 下午10:59
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  解压包头
 */

namespace EasySwoole\Keyword\Base;

class Protocol
{
    public static function pack(string $data): string
    {
        return pack('N', strlen($data)) . $data;
    }

    public static function packDataLength(string $head): int
    {
        return unpack('N', $head)[1];
    }

    public static function unpack(string $data): string
    {
        return substr($data, 4);
    }
}
