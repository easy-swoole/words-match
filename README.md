---
title: easyswoole 内容检测
meta:
  - name: description
    content: easyswoole 内容检测
  - name: keywords
    content: swoole|easyswoole|内容检测|敏感词|检测
---

# words-match

words-match组件是基于字典树(DFA)并利用UnixSock通讯和自定义进程实现，开发本组件的目的是帮小伙伴们快速部署内容检测服务。

## 使用场景

跟文字内容相关的产品都有应用场景。

比如：

博客类的文章，评论的检测

聊天内容的检测

对垃圾内容的屏蔽

## 安装

```
composer require easyswoole/words-match
```

## 准备词库

服务启动的时候会一行一行将数据读出来，每一行的第一列为敏感词，其它列为附属信息

```
php,是世界上,最好的语言
java
golang
程序员
代码
逻辑
php※程序员
```

## 代码示例
```php
use EasySwoole\WordsMatch\Config;
use EasySwoole\WordsMatch\WMServer;

require 'vendor/autoload.php';


$http = new Swoole\Http\Server("127.0.0.1", 9501);

$config = new Config();

$config->setDict(__DIR__.'/tests/dictionary.txt');

WMServer::getInstance($config)->attachServer($http);

$http->on("request", function ($request, $response) {
    if(isset($request->get['world'])){
        $world = $request->get['world'];
    }else{
        $world = "计算机①级考试🐂替考+++++++++++++我";
    }
    $ret = WMServer::getInstance()->detect($world);
    $response->header("Content-Type", "application/json;charset=utf-8");
    $response->write(json_encode($ret,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
});

$http->start();

```