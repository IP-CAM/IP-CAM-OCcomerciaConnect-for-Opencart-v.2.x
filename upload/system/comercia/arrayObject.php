<?php
namespace comercia;

class ArrayObject
{
    private $data;

    function __construct(&$data)
    {
        $this->data =& $data;
    }

    function __get($name)
    {
        return @$this->data[$name] ?: "";
    }

    function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    function remove($name)
    {
        unset($this->data[$name]);
    }

    function all()
    {
        return $this->data;
    }

    function timestamp($field)
    {
        $data = $this->data[$field];
        if (!is_numeric($data)) {
            Util::dateTimeHelper()->toTimestamp($data);
        }
        return $data;
    }

    function bool($field, $default = false)
    {
        if (!isset($this->data[$field])) {
            return $default;
        }

        $data = $this->data[$field];
        if ($data == "false") {
            return false;
        } else {
            return $data ? true : false;
        }
    }

    function allPrefixed($prefix, $removePrefix = true)
    {
        $result = [];
        $prefixLen = strlen($prefix);
        foreach ($this->data as $key => $val) {
            if (substr($key, 0, $prefixLen) == $prefix) {
                if ($removePrefix) {
                    $key = substr($key, $prefixLen);
                }
                $result[$key] = $val;
            }
        }
        return $result;
    }
}

?>
