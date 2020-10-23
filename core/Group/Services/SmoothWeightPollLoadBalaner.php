<?php

namespace Group\Services;

use Group\Config\Config;

class SmoothWeightPollLoadBalaner extends AbstractLoadBalaner
{   
    public $nodes = [];

    public $weight = [];

    public $maxCount = 10000;

    public $count = 0;

    public function __construct()
    {
        $this->maxCount = Config::get('service::balancer.maxcount', 10000);
    }
    
    public function select($addrs)
    {
        if (!$addrs) {
            return false;
        }

        if (array_diff($addrs, array_keys($this->nodes)) || count($addrs) != count($this->nodes)) {
            $this->rebuildNodes($addrs);
        }

        if (empty($this->weight)) {
            return false;
        }

        krsort($this->weight, SORT_NATURAL);
        $addr = current($this->weight);

        if ($this->maxCount > $this->count) {
            $this->count++;
        } else {
            $this->nodes[$addr] = $this->nodes[$addr] - count($this->nodes);

            $weight = [];
            foreach ($this->nodes as $a => $n) {
                $n = $n + 1;
                $weight[$n."_".$a] = $a;
                $this->nodes[$a] = $n;
            }
            $this->weight = $weight;
            $this->count = 0;
        }

        return $addr;
    }

    protected function rebuildNodes($addrs)
    {
        $nNodes = [];
        $weight = [];
        foreach ($addrs as $addr) {
            if (isset($this->nodes[$addr])) {
                $nNodes[$addr] = $this->nodes[$addr];
            } else {
                $nNodes[$addr] = 1;
            }

            $weight[$nNodes[$addr]."_".$addr] = $addr;
        }

        $this->nodes = $nNodes;
        $this->weight = $weight;
    }
}