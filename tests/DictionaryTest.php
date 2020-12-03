<?php
/**
 * @CreateTime:   2020/10/27 12:24 上午
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2020) Easyswoole all rights reserved
 * @Description:  字典单测
 */
namespace EasySwoole\WordsMatch\Tests;

use EasySwoole\WordsMatch\Dictionary\DetectResult;
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
        $this->assertEquals($this->createDetectResult([
            'word' => '包夜',
            'location' => [
                [
                    'word' => '包夜',
                    'location' => [
                        2
                    ],
                    'length' => 2
                ]
            ],
            'count' => 1,
            'remark' => '',
            'type' => 1
        ]), $res[0]);

        $this->assertEquals($this->createDetectResult([
            'word' => '微信',
            'location' => [
                [
                    'word' => '微信',
                    'location' => [
                        5
                    ],
                    'length' => 2
                ]
            ],
            'count' => 1,
            'remark' => '',
            'type' => 1
        ]), $res[1]);

        $this->assertCount(2, $res);
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
        $this->assertEquals(
            $this->createDetectResult(
                [
                    'word' => '包夜',
                    'location' => [
                        [
                            'word' => '包夜',
                            'location' => [
                                2,10
                            ],
                            'length' => 2
                        ]
                    ],
                    'count' => 2,
                    'remark' => '',
                    'type' => 1
                ]
            )
            , $res[0]
        );
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
        $this->assertEquals(
            $this->createDetectResult(
                [
                    'word' => '6位qq',
                    'location' => [
                        [
                            'word' => '6位qq',
                            'location' => [
                                2
                            ],
                            'length' => 4
                        ]
                    ],
                    'count' => 1,
                    'remark' => '卖qq的',
                    'type' => 1
                ]
            )
            , $res[0]
        );
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
        $this->assertEquals(
            $this->createDetectResult(
                [
                    'word' => '考试∮替考',
                    'location' => [
                        [
                            'word' => '考试',
                            'location' => [
                                5
                            ],
                            'length' => 2
                        ],
                        [
                            'word' => '替考',
                            'location' => [
                                8
                            ],
                            'length' => 2
                        ]
                    ],
                    'count' => 1,
                    'remark' => '',
                    'type' => 2
                ],
            )
            , $res[0]
        );
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
        $this->assertEquals(
            $this->createDetectResult(
                [
                    'word' => '考试∮替考',
                    'location' => [
                        [
                            'word' => '考试',
                            'location' => [
                                5
                            ],
                            'length' => 2
                        ],
                        [
                            'word' => '替考',
                            'location' => [
                                8, 13, 20
                            ],
                            'length' => 2
                        ]
                    ],
                    'count' => 1,
                    'remark' => '',
                    'type' => 2
                ],
            )
            , $res[0]
        );
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
        $this->assertEquals(
            $this->createDetectResult(
                [
                    'word' => '赌博∮lol',
                    'location' => [
                        [
                            'word' => 'lol',
                            'location' => [
                                5
                            ],
                            'length' => 3
                        ],
                        [
                            'word' => '赌博',
                            'location' => [
                                9
                            ],
                            'length' => 2
                        ]
                    ],
                    'count' => 1,
                    'remark' => '英雄联盟赌博相关',
                    'type' => 2
                ],
            )
            , $res[0]
        );
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
        $this->assertEquals(
            $this->createDetectResult(
                [
                    'word' => '微信',
                    'location' => [
                        [
                            'word' => '微信',
                            'location' => [
                                25
                            ],
                            'length' => 2
                        ],
                    ],
                    'count' => 1,
                    'remark' => '',
                    'type' => 1
                ],
            )
            , $res[0]
        );
        $this->assertEquals(
            $this->createDetectResult(
                [
                    'word' => '考试∮替考',
                    'location' => [
                        [
                            'word' => '考试',
                            'location' => [
                                5
                            ],
                            'length' => 2
                        ],
                        [
                            'word' => '替考',
                            'location' => [
                                8
                            ],
                            'length' => 2
                        ]
                    ],
                    'count' => 1,
                    'remark' => '',
                    'type' => 2
                ],
            )
            , $res[1]
        );
    }

    private function getDictionary():Dictionary
    {
        $dictionary = new Dictionary();
        $dictionary->load($this->dictionary);
        return $dictionary;
    }

    private function createDetectResult(array $arr):DetectResult
    {
        return new DetectResult($arr);
    }

}
