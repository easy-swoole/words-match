# EasySwoole Keyword组件

## 简介
 
#### 能做什么？
 
 此组件可用来进行实时关键词检测,异步数据检测清洗等，并且已经提升为
 Easyswoole的多进程关键词服务，服务内以文件方式加载词库，并且支持实时
 增、删关键词。
 
 `感谢Easyswoole开发组其它小伙伴们的指导和AbelZhou开源的字典树供我学习和集成到关键词服务组件中`
 

## 使用

#### 1. 下载

```
composer require easyswoole/keyword
```
#### 1. 准备词库文件
第一列为关键词，以"\t"分割，后面的参数全为其它信息，会当关键词命中的时候将其它信息返回
```
我
我是
我叫
```

#### 3. EasyswooleEvent.php

```
<?php
namespace EasySwoole\EasySwoole;

use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use EasySwoole\Keyword\KeywordClient;
use EasySwoole\Keyword\KeywordServer;

class EasySwooleEvent implements Event
{

    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');

    }

    public static function mainServerCreate(EventRegister $register)
    {
        // TODO: Implement mainServerCreate() method.
        KeywordServer::getInstance()
            ->setTempDir(EASYSWOOLE_TEMP_DIR)
            ->setKeywordPath('/Users/xx/sites/xx/keyword.txt')
            ->attachToServer(ServerManager::getInstance()
            ->getSwooleServer());
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // TODO: Implement onRequest() method.
        KeywordClient::getInstance()->append('我叫Easyswoole', []);
        KeywordClient::getInstance()->append('我叫Es', []);
        $res = KeywordClient::getInstance()->search('我叫Easyswoole');
        var_dump($res);
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
    }
}

```

#### 4. 输出结果

```
array(3) {
  ["16815254531798dc21ee979d1d9c6675"]=>
  array(3) {
    ["keyword"]=>
    string(3) "我"
    ["other"]=>
    array(0) {
    }
    ["count"]=>
    int(1)
  }
  ["77e4a7023ca547689990f2aa4c81f33b"]=>
  array(3) {
    ["keyword"]=>
    string(6) "我叫"
    ["other"]=>
    array(0) {
    }
    ["count"]=>
    int(1)
  }
  ["1695a633cf1782cab389ab3bf3fcb1a0"]=>
  array(3) {
    ["keyword"]=>
    string(16) "我叫Easyswoole"
    ["other"]=>
    array(0) {
    }
    ["count"]=>
    int(1)
  }
}
```

