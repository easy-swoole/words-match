<?php
/**
 * @CreateTime:   2020/10/22 12:08 上午
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2020) Easyswoole all rights reserved
 * @Description:  library 单测
 */
namespace EasySwoole\WordsMatch\Test;

use EasySwoole\Spl\SplFileStream;
use EasySwoole\WordsMatch\Config\Config;
use EasySwoole\WordsMatch\Config\WordsMatchConfig;
use EasySwoole\WordsMatch\Extend\Protocol\Package;
use EasySwoole\WordsMatch\Library;
use PHPUnit\Framework\TestCase;

class LibraryTest extends TestCase
{

    private $library;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->library = new Library();
        parent::__construct($name, $data, $dataName);
    }

    private function buildTree()
    {
        WordsMatchConfig::getInstance([
            'wordBanks' => [
                'other' => EASYSWOOLE_ROOT . '/vendor/easyswoole/words-match/test/test.txt',
            ],
        ]);
        Library::getInstance()->buildTrees();
    }

    public function testDetectNormal()
    {
        $this->buildTree();
        $package = new Package();
        $package->setCommand(Package::ACTION_SEARCH);
        $package->setContent('php入门');
        $package->setWordBanks(['other']);
        $res = Library::getInstance()->detect($package, $this->library->unserializeCache());
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
        $this->buildTree();
        $package = new Package();
        $package->setCommand(Package::ACTION_SEARCH);
        $package->setContent('easyswoole简称es');
        $package->setWordBanks(['other']);
        $res = Library::getInstance()->detect($package, $this->library->unserializeCache());
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

    public function testDetectNormalCompound()
    {
        $this->buildTree();
        $package = new Package();
        $package->setCommand(Package::ACTION_SEARCH);
        $package->setContent('easyswoole是简称es');
        $package->setWordBanks(['other']);
        $res = Library::getInstance()->detect($package, $this->library->unserializeCache());
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
        $package = new Package();
        $package->setCommand(Package::ACTION_APPEND);
        $package->setWord('ES');
        $package->setOtherInfo([
            'other'
        ]);
        $package->setWordBanks(['other']);
        Library::getInstance()->append($package);
        $this->buildTree();
        $package = new Package();
        $package->setCommand(Package::ACTION_SEARCH);
        $package->setContent('测试ES');
        $package->setWordBanks(['other']);
        $res = Library::getInstance()->detect($package, $this->library->unserializeCache());
        $expected = json_encode([
            [
                'word' => 'ES',
                'other' => ['other'],
                'count' => 1,
                'location' => [2],
                'type' => 1
            ]
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode($res, JSON_UNESCAPED_UNICODE));
    }

    public function testRemove()
    {
        $this->buildTree();
        $package = new Package();
        $package->setCommand(Package::ACTION_SEARCH);
        $package->setContent('测试ES');
        $package->setWordBanks(['other']);
        $res = Library::getInstance()->detect($package, $this->library->unserializeCache());
        $expected = json_encode([
            [
                'word' => 'ES',
                'other' => ['other'],
                'count' => 1,
                'location' => [2],
                'type' => 1
            ]
        ], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode($res, JSON_UNESCAPED_UNICODE));

        $package = new Package();
        $package->setCommand(Package::ACTION_REMOVE);
        $package->setWordBanks(['other']);
        $package->setWord('ES');
        Library::getInstance()->remove($package);

        $this->buildTree();

        $package = new Package();
        $package->setCommand(Package::ACTION_SEARCH);
        $package->setContent('测试ES');
        $package->setWordBanks(['other']);
        $res = Library::getInstance()->detect($package, $this->library->unserializeCache());
        $expected = json_encode([], JSON_UNESCAPED_UNICODE);
        $this->assertEquals($expected, json_encode($res, JSON_UNESCAPED_UNICODE));
    }

}
