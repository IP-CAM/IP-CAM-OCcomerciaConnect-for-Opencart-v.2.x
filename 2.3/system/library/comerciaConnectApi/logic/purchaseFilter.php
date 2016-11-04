<?php
namespace comerciaConnect\logic;
class PurchaseFilter
{
    private $session;
    var $filters=array();

    function __construct($session)
    {
        $this->session=$session;
    }

    function filter($field,$value,$operator="="){
        $this->filters[]=array("field"=>$field,"operator"=>$operator, "value"=>$value);
        return $this;
    }

    function getData(){
        $data=$this->session->post("purchase/getByFilter",$this);
        $result=array();
        foreach($data["data"] as $purchase){
            $result[]=new Purchase($this->session,$purchase);
        }
        return $result;
    }
}
?>