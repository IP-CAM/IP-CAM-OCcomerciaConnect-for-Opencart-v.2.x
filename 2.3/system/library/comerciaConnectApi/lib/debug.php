<?php
namespace comerciaConnect\lib;
class Debug
{
    static function write($message)
    {
        global $is_in_debug;
        if (isset($is_in_debug) && $is_in_debug) {
            print_r($message);
        }
    }
}
?>