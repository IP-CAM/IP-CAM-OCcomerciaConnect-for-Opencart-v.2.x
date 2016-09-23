<?php
namespace comerciaConnect\logic;
class ProductCategory{
    var $id;
    var $name;

    private $session;
    function __construct($session)
    {
        $this->session=$session;
    }

    function save(){
        $this->session->post("productCategory/save",$this);
    }
}