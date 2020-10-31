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
    public function testDetectNormal()
    {
        $dictionary = $this->getDictionary();
        $res = $dictionary->detect('php入门');
        $expected = json_encode([
            [
                'word' => 'php',
                'other' => [],
                'count' => 1,
                'location' => [0],
                'type' => 1
            ]
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode($res, JSON_UNESCAPED_UNICODE));
    }

    public function testDetectCompound()
    {
        $dictionary = $this->getDictionary();
        $res = $dictionary->detect('easyswoole简称es');
        $expected = json_encode([
            [
                'word' => 'easyswoole※es',
                'other' => [],
                'count' => 1,
                'location' => [0,12],
                'type' => 2
            ],
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode($res, JSON_UNESCAPED_UNICODE));
    }

    public function testDetectNormalAndCompound()
    {
        $dictionary = $this->getDictionary();
        $res = $dictionary->detect('easyswoole是简称es');
        $expected = json_encode([
            [
                'word' => '是',
                'other' => [],
                'count' => 1,
                'location' => [10],
                'type' => 1
            ],
            [
                'word' => 'easyswoole※es',
                'other' => [],
                'count' => 1,
                'location' => [0,13],
                'type' => 2
            ],
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode($res, JSON_UNESCAPED_UNICODE));
    }

    public function testAppend()
    {
        $dictionary = $this->getDictionary();
        $dictionary->append('ES');
        $res = $dictionary->detect('测试ES');
        $expected = json_encode([
            [
                'word' => 'ES',
                'other' => [],
                'count' => 1,
                'location' => [2],
                'type' => 1
            ]
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode($res, JSON_UNESCAPED_UNICODE));
    }

    public function testRemove()
    {
        $dictionary = $this->getDictionary();
        $dictionary->remove('ES');
        $res = $dictionary->detect('测试ES');
        $expected = json_encode([]);
        $this->assertEquals($expected, json_encode($res, JSON_UNESCAPED_UNICODE));
    }


    private function getDictionary():Dictionary
    {
        $dictionary = new Dictionary();
        $dictionary->load($this->dictionary);
        return $dictionary;
    }

}
