<?php
namespace comerciaConnect\logic;
class ProductDescription{
    var $language;
    var $name;
    var $description;

    function __construct($language="",$name="",$description="")
    {
        $this->name=$name;
        $this->language=$language;
        $this->description=$description;
    }
}