<?php
/**
 * @CreateTime:   2019/11/7 下午11:58
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  特殊符号过滤
 */
namespace EasySwoole\WordsMatch\Extend;

use EasySwoole\Component\Singleton;

class SpecialSymbolFilter {

    use Singleton;

    public function filterEmoji(&$text)
    {
        $text = preg_replace("/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u",'', $text);
    }

    public function chinese(&$text) {
        $text = preg_replace("/[^\x{4e00}-\x{9fa5}]/iu",'',$text);
    }

    public function chineseEnglishNumber(&$text)
    {
        $text = preg_replace("/[^\x{4e00}-\x{9fa5}a-zA-Z0-9]/u",'',$text);
    }
}
