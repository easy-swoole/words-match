<?php


namespace EasySwoole\WordsMatch\Dictionary;


use EasySwoole\Spl\SplBean;

class DetectResult extends SplBean
{
    /** @var string */
    public $word;
    /** @var array */
    public $other;
    /** @var integer */
    public $count = 0;
    public $location = [];
    /** @var integer */
    public $type;

    /**
     * @return string
     */
    public function getWord(): string
    {
        return $this->word;
    }

    /**
     * @param string $word
     */
    public function setWord(string $word): void
    {
        $this->word = $word;
    }

    /**
     * @return array
     */
    public function getOther(): array
    {
        return $this->other;
    }

    /**
     * @param array $other
     */
    public function setOther(array $other): void
    {
        $this->other = $other;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

    /**
     * @param int $count
     */
    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    /**
     * @return array
     */
    public function getLocation(): array
    {
        return $this->location;
    }

    /**
     * @param array $location
     */
    public function setLocation(array $location): void
    {
        $this->location = $location;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType(int $type): void
    {
        $this->type = $type;
    }
}
