<?php
include_once '../vendor/autoload.php';
use EasySwoole\Keyword\KeywordConfig;
use EasySwoole\Keyword\Keyword;
$keywordConfig = new KeywordConfig();
try {
    $keywordConfig->setSourceType(KeywordConfig::FILE)->setLibraryPath('/Users/yuzhao3/sites/SelfTrieTree/src/dict.txt');
    $keyword = new Keyword($keywordConfig);
    $res = $keyword->search('');
    var_dump($res);die;
} catch (\Exception $e) {
}