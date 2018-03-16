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
    static function write($message,$type)
    {
        if(defined("CC_DEBUG") && CC_DEBUG){
            if(defined("CC_PATH_LOG")){
                $logFile=CC_PATH_LOG;
            }else{
                $logFile="ccLog.log";
            }
            if(!array(CC_DEBUG) || in_array($type,CC_DEBUG)) {
                file_put_contents($logFile, "[" . date("d-m-Y H:i:s") . "] " . $message . "\n", FILE_APPEND);
            }
        }
    }

    public static function writeMemory($string)
    {
        if(function_exists("memory_get_usage") && defined("CC_DEBUG") && CC_DEBUG){
            $unit=array('b','kb','mb','gb','tb','pb');
            $bytes=memory_get_usage();
            $converted=round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2).' '.$unit[$i];
            self::write($string."(".$converted.")","memory");
        }
    }
}
?>