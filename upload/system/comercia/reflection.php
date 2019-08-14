<?php
namespace comercia;

class Reflection
{
    function getProperty($instance, $field)
    {
        $class = get_class($instance);
        $reflectionClass = new \ReflectionClass($class);
        $reflectionProperty = $reflectionClass->getProperty($field);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->getValue($instance);
    }

    function setProperty($instance, $field, $value)
    {
        $class = get_class($instance);
        $reflectionClass = new \ReflectionClass($class);
        $reflectionProperty = $reflectionClass->getProperty($field);
        $reflectionProperty->setAccessible(true);
        return $reflectionProperty->setValue($instance, $value);
    }

    function getFunctionCode($function)
    {
        $reflFunc = new \ReflectionFunction($function);
        $start=$reflFunc->getStartLine();
        $end=$reflFunc->getEndLine();
        $content=file_get_contents($reflFunc->getFileName());
        $exp=explode("\n",$content);
        $functionArea=array_slice($exp,$start,$end-$start-1);
        $code=implode("\n",$functionArea);
        return $code;
    }
}

?>
