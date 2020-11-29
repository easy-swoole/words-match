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

#### 服务注册
```php
<?php
namespace EasySwoole\EasySwoole;

use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\WordsMatch\WMServer;
use EasySwoole\WordsMatch\Config;

class EasySwooleEvent implements Event
{
    public static function initialize()
    {
        date_default_timezone_set('Asia/Shanghai');
    }

    public static function mainServerCreate(EventRegister $register)
    {
        $config = Config::getInstance()
            ->setWorkerNum(3)
            ->setMaxMEM('1M')
            ->setSockDIR(EASYSWOOLE_ROOT . '/Temp/')
            ->setDict('/Users/guoyuzhao/sites/wm-2.0/test.txt');
        WMServer::getInstance($config)->attachServer(ServerManager::getInstance()->getSwooleServer());
    }
}

```

#### 客户端使用

````php
<?php
namespace App\HttpController;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\WordsMatch\WMServer;

class Index extends Controller
{

    public function index()
    {
        // 内容检测
        WMServer::getInstance()->detect('待检测内容');
    }

    public function reload()
    {
        // 此方法为异步实现，执行此方法后会重新reload词库，词库生效时间根据词库的大小决定
        WMServer::getInstance()->reload();
    }

}
````

#### 检测内容

> php，，，，程序员。。。java， 程序员

#### 检测结果

````text
array(4) {
  [0]=> // 命中的每个词的信息
  object(EasySwoole\WordsMatch\Dictionary\DetectResult)#96 (5) {
    ["word"]=>
    string(3) "php" // 命中的词
    ["other"]=> // 词的其它信息
    array(2) {
      [0]=>
      string(12) "是世界上"
      [1]=>
      string(15) "最好的语言"
    }
    ["count"]=> // 命中的次数
    int(1)
    ["location"]=> // 命中的位置信息
    array(1) {
      ["php"]=>
      array(2) {
        ["location"]=> // 位置
        array(1) {
          [0]=>
          int(0)
        }
        ["length"]=> // 词的长度
        int(3)
      }
    }
    ["type"]=> // 词的类型，1: 普通词 2: 复合词
    int(1)
  }
  [1]=>
  object(EasySwoole\WordsMatch\Dictionary\DetectResult)#102 (5) {
    ["word"]=>
    string(9) "程序员"
    ["other"]=>
    array(0) {
    }
    ["count"]=>
    int(2)
    ["location"]=>
    array(1) {
      ["程序员"]=>
      array(2) {
        ["location"]=>
        array(2) {
          [0]=>
          int(7)
          [1]=>
          int(19)
        }
        ["length"]=>
        int(3)
      }
    }
    ["type"]=>
    int(1)
  }
  [2]=>
  object(EasySwoole\WordsMatch\Dictionary\DetectResult)#103 (5) {
    ["word"]=>
    string(4) "java"
    ["other"]=>
    array(0) {
    }
    ["count"]=>
    int(1)
    ["location"]=>
    array(1) {
      ["java"]=>
      array(2) {
        ["location"]=>
        array(1) {
          [0]=>
          int(13)
        }
        ["length"]=>
        int(4)
      }
    }
    ["type"]=>
    int(1)
  }
  [3]=>
  object(EasySwoole\WordsMatch\Dictionary\DetectResult)#104 (5) {
    ["word"]=>
    string(15) "php※程序员"
    ["other"]=>
    array(0) {
    }
    ["count"]=>
    int(1)
    ["location"]=>
    array(2) {
      ["php"]=>
      array(2) {
        ["location"]=>
        array(1) {
          [0]=>
          int(0)
        }
        ["length"]=>
        int(3)
      }
      ["程序员"]=>
      array(2) {
        ["location"]=>
        array(2) {
          [0]=>
          int(7)
          [1]=>
          int(19)
        }
        ["length"]=>
        int(3)
      }
    }
    ["type"]=>
    int(2)
  }
}
````
