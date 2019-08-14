<?php
namespace comercia;

class ArrayObject implements \ArrayAccess, \Countable, \Iterator
{
    private $data;
    private $useSubObjects;

    function __construct(&$data, $useSubObjects = false)
    {

        $this->data =& $data;
        $this->useSubObjects = $useSubObjects;
    }

    function __get($name)
    {
        if ($this->useSubObjects && is_array($this->data[$name])) {
            $dataArray=&$this->data[$name]?:[];
            return new ArrayObject($dataArray, true);
        }
        return @$this->data[$name] ?: "";
    }

    function get($name)
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
        return Util::arrayHelper()->allPrefixed($this->all(), $prefix, $removePrefix);
    }


    //for easy itteration
    private $position = 0;

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->data[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->data[$this->position]);
    }


    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }


    function count()
    {
        return count($this->data);
    }
}

?>
