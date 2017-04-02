<?php

class ContainsInOrder extends PHPUnit_Framework_Constraint
{
    protected $expected;

    public function __construct(array $expected)
    {
        parent::__construct();
        $this->expected = $expected;
    }

    public function matches($other)
    {
        $cursor = 0;

        foreach ($this->expected as $substring) {
            $next = strpos($other, $substring, $cursor);

            if ($next === false) {
                return false;
            }

            $cursor = $next + strlen($substring) + 1;
        }

        return true;
    }

    public function toString()
    {
        return 'matches array of '.count($this->expected).' strings';
    }

    protected function failureDescription($other)
    {
        if (is_string($other)) {
            return 'string '.strlen($other).' bytes long '.$this->toString();
        } else {
            return $this->exporter->export($other).' '.$this->toString();
        }
    }
}
