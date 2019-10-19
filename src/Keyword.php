<?php
/**
 * @CreateTime:   2019-10-16 23:21
 * @Author:       huizhang  <tuzisir@163.com>
 * @Copyright:    copyright(2019) Easyswoole all rights reserved
 * @Description:  对外提供的api
 */
namespace EasySwoole\Keyword;

class Keyword extends AbstractKeyword
{

    public function append(string $keyword, $otherInfo=[])
    {
        // TODO: Implement append() method.
        $this->tree->append($keyword, $otherInfo);
    }

    public function search(string $text): array
    {
        // TODO: Implement search() method.
        return $this->tree->search($text);
    }

    public function remove(string $keyword, bool $delChildTree=false)
    {
        // TODO: Implement remove() method.
        return $this->tree->remove($keyword, $delChildTree);
    }

    public function getTree(): array
    {
        // TODO: Implement getTree() method.
        return $this->tree->getTree();
    }

}