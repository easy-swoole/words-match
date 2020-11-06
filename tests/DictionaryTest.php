<?php
/**
 * @CreateTime:   2020/10/27 12:24 上午
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2020) Easyswoole all rights reserved
 * @Description:  字典单测
 */
namespace EasySwoole\WordsMatch\Tests;

use EasySwoole\WordsMatch\Dictionary\Dictionary;
use PHPUnit\Framework\TestCase;

class DictionaryTest extends TestCase
{

    private $dictionary = __DIR__.'/dictionary.txt';

    /**
     * 普通词检测
     *
     * CreateTime: 2020/11/6 12:35 上午
     */
    public function testDetectNormal()
    {
        $dictionary = $this->getDictionary();
        $res = $dictionary->detect('⑩⑧包夜🔞微信+');
        $expected = json_encode([
            [
                'word' => '包夜',
                'other' => [],
                'count' => 1,
                'location' => [2],
                'type' => 1
            ],
            [
                'word' => '微信',
                'other' => [],
                'count' => 1,
                'location' => [5],
                'type' => 1
            ],
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode(array_values($res), JSON_UNESCAPED_UNICODE));
    }

    /**
     * 普通词检测(count)
     *
     * CreateTime: 2020/11/6 12:35 上午
     */
    public function testDetectNormalCount()
    {
        $dictionary = $this->getDictionary();
        $res = $dictionary->detect('⑩⑧包夜🔞微--信+包夜');
        $expected = json_encode([
            [
                'word' => '包夜',
                'other' => [],
                'count' => 2,
                'location' => [2, 10],
                'type' => 1
            ],
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode(array_values($res), JSON_UNESCAPED_UNICODE));
    }

    /**
     * 普通词检测其它信息
     *
     * CreateTime: 2020/11/6 12:35 上午
     */
    public function testDetectNormalOther()
    {
        $dictionary = $this->getDictionary();
        $res = $dictionary->detect('⑩⑧6位qq🔞微--信+');
        $expected = json_encode([
            [
                'word' => '6位qq',
                'other' => ['卖qq的'],
                'count' => 1,
                'location' => [2],
                'type' => 1
            ],
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode(array_values($res), JSON_UNESCAPED_UNICODE));
    }

    /**
     * 复合词检测
     *
     * CreateTime: 2020/11/6 12:35 上午
     */
    public function testDetectCompound()
    {
        $dictionary = $this->getDictionary();
        $res = $dictionary->detect('计算机①级考试🐂替考+++++++++++++我');
        $expected = json_encode([
            [
                'word' => '考试※替考',
                'other' => [],
                'count' => 1,
                'location' => [5,8],
                'type' => 2
            ],
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode(array_values($res), JSON_UNESCAPED_UNICODE));
    }

    /**
     * 复合词位置
     *
     * CreateTime: 2020/11/6 12:35 上午
     */
    public function testDetectCompoundLocation()
    {
        $dictionary = $this->getDictionary();
        $res = $dictionary->detect('计算机①级考试🐂替考+++替考+++++替考+++++我');
        $expected = json_encode([
            [
                'word' => '考试※替考',
                'other' => [],
                'count' => 1,
                'location' => [5,8,13,20],
                'type' => 2
            ],
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode(array_values($res), JSON_UNESCAPED_UNICODE));
    }

    /**
     * 复合词位置
     *
     * CreateTime: 2020/11/6 12:35 上午
     */
    public function testDetectCompoundOther()
    {
        $dictionary = $this->getDictionary();
        $res = $dictionary->detect('s10赛季lol🈲赌博+++++');
        $expected = json_encode([
            [
                'word' => '赌博※lol',
                'other' => ['英雄联盟赌博相关'],
                'count' => 1,
                'location' => [5,9],
                'type' => 2
            ],
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode(array_values($res), JSON_UNESCAPED_UNICODE));
    }

    /**
     * 普通词+组合词
     *
     * CreateTime: 2020/11/6 12:51 上午
     */
    public function testDetectNormalAndCompound()
    {
        $dictionary = $this->getDictionary();
        $res = $dictionary->detect('计算机①级考试🐂替考+++++++++++++我🐂微信');
        $expected = json_encode([
            [
                'word' => '考试※替考',
                'other' => [],
                'count' => 1,
                'location' => [5,8],
                'type' => 2
            ],
            [
                'word' => '微信',
                'other' => [],
                'count' => 1,
                'location' => [25],
                'type' => 1
            ],
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode(array_values($res), JSON_UNESCAPED_UNICODE));
    }

    /**
     * 添加词
     *
     * CreateTime: 2020/11/6 12:52 上午
     */
    public function testAppend()
    {
        $dictionary = $this->getDictionary();
        $dictionary->append('威信');
        $res = $dictionary->detect('出售答案可+威信');
        $expected = json_encode([
            [
                'word' => '出售答案',
                'other' => [],
                'count' => 1,
                'location' => [0],
                'type' => 1
            ],
            [
                'word' => '威信',
                'other' => [],
                'count' => 1,
                'location' => [6],
                'type' => 1
            ],
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode(array_values($res), JSON_UNESCAPED_UNICODE));
    }

    /**
     * 移除词
     *
     * CreateTime: 2020/11/6 12:55 上午
     */
    public function testRemove()
    {
        $dictionary = $this->getDictionary();
        $dictionary->remove('威信');
        $res = $dictionary->detect('出售答案可+威信');
        $expected = json_encode([
            [
                'word' => '出售答案',
                'other' => [],
                'count' => 1,
                'location' => [0],
                'type' => 1
            ],
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode(array_values($res), JSON_UNESCAPED_UNICODE));
    }

    private function getDictionary():Dictionary
    {
        $dictionary = new Dictionary();
        $dictionary->load($this->dictionary);
        return $dictionary;
    }

}
