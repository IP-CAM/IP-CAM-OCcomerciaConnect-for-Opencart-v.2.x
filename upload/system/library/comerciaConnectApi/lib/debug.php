<?php
namespace comerciaConnect\lib;
/**
 * Used for debug purposes
 * @author Mark Smit <m.smit@comercia.nl>
 */
class Debug
{
    /**
     * Prints messages when debug is turned on
     * @param String $message The message to print
     * @global bool $is_in_debug
     */
    static function write($message)
    {
        global $is_in_debug;
        if (isset($is_in_debug) && $is_in_debug) {
            print_r($message);
        }
    }
}
?>