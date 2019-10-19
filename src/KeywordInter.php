<?php
/**
 * @CreateTime:   2019-10-16 21:06
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  定义对外提供的接口
 */
namespace EasySwoole\Keyword;

interface KeywordInter {

    public function append(string $keyword, $otherInfo);

    public function search(string $text): array ;

    public function remove(string $keyword, bool $delChildTree=false);

    public function getTree(): array ;

}