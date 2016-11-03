<?php
namespace comerciaConnect\logic;
class ProductDescription
{
    var $language;
    var $name;
    var $description;

    function __construct($language = "", $name = "", $description = "")
    {
        if (is_array($name)) {
            $data = $name;
            foreach ($data as $key => $value) {
                $this->{$key} = $value;
            }
        } else {
            $this->name = $name;
            $this->language = $language;
        }
        $this->description = $description;
    }
}