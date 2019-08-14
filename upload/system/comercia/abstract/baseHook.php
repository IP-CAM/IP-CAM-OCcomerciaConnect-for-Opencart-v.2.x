<?php

namespace comercia;
abstract class BaseHook extends \Model
{
    abstract function run();

    var $result = [];
    var $dir = DIR_APPLICATION;

    function addOperation($file, $position, $search, $add, $offset = 0)
    {
        $this->result[$file][] = ["position" => $position, "search" => $search, "add" => $add, "offset" => $offset];
    }
}

?>