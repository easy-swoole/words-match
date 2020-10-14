<?php
/**
 * @CreateTime:   2020/10/12 10:42 下午
 * @Author:       huizhang  <2788828128@qq.com>
 * @Copyright:    copyright(2020) Easyswoole all rights reserved
 * @Description:  wrds-match默认配置
 */
namespace EasySwoole\WordsMatch\Config;

class Config
{

    public const COMPOUND_WORD_SEPARATOR = '※';

    public const MANAGER_PROCESS_SOCK = EASYSWOOLE_ROOT . '/Temp/words-match.manager.sock';

    public const WORDSMATCH_SERIALIZE = EASYSWOOLE_ROOT.'/Temp/words-match-serialize';

    public const GROUPS_SERIALIZE = EASYSWOOLE_ROOT.'/Temp/words-match-groups-serialize';

    public const WORD_TYPE_NORMAL = 1;

    public const WORD_TYPE_COMPOUND = 2;

    public const WORD_TYPE_NORMAL_AND_COMPOUND = 3;
}
