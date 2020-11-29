<?php


namespace EasySwoole\WordsMatch\Dictionary;


use EasySwoole\Spl\SplBean;

class DetectResult extends SplBean
{
    public $word;
    public $other;
    public $count = 0;
    public $location = [];
    public $type;

    public function getWord(): string
    {
        return $this->word;
    }

    public function setWord(string $word): void
    {
        $this->word = $word;
    }

    public function getOther(): array
    {
        return $this->other;
    }

    public function setOther(array $other): void
    {
        $this->other = $other;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    public function getLocation(): array
    {
        return $this->location;
    }

    public function setLocation(array $location): void
    {
        $this->location = $location;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }
}
